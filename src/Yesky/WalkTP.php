<?php
namespace Yesky\WalkTP;

use Pocketmine\item\Item;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\event\TranslationContainer;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\level\Position;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\Vector3;
use Yesky\WalkTP\Cooldown;

#####################################
# LeetWalkTP
# Created by LEET Development Team.
# You dont have permission to edit and share this plugin.
# Author Yesky
#####################################


class WalkTP extends PluginBase implements Listener {
	private $placeQueue = [ ], $editmode = [];
	public $walktp;
	public function onEnable() {
	 $this->getLogger()->info(TextFormat::GREEN . "LEETWalkTP enabled");
	 $this->getLogger()->info(TextFormat::GREEN . "LEETWalkTP created by LEET Development team author: yesky"); 		
    if(!file_exists($this->getDataFolder() . "config.yml")){
      	@mkdir($this->getDataFolder());
      	file_put_contents($this->getDataFolder()."config.yml", $this->getResource("config.yml"));
    }
	 $this->loadWalkTp();
	 $this->getServer()->getPluginManager()->registerEvents($this, $this);
	}	
	public function onDisable() {
		$this->save();
	   $this->getLogger()->info(TextFormat::GREEN . "LEETWalkTP disaabled");
	}
	public function loadWalkTp() {
		@mkdir($this->getDataFolder());
		$this->walktp = (new Config($this->getDataFolder()."walktp.yml", Config::YAML))->getAll();
	}
	public function save() {
		$walktp = new Config($this->getDataFolder()."walktp.yml", Config::YAML);
		$walktp->setAll($this->walktp);
		$walktp->save();
	}
	public function onCommand(CommandSender $sender, Command $command, $label, Array $args) {
	 $walktpblock = $this->getConfig()->get("walktpblock");
	 $cmd = $command->getName();
		if (!isset($args[0])) {
			return false;
		}
		switch(strtolower($args[0])) {
			case "set" :
				if (!$sender->hasPermission("walktp.commands.set")) {
					$sender->sendMessage(new TranslationContainer(TextFormat::RED."%commands.generic.permission"));
					break;
				}
				$sender->sendMessage(TextFormat::BLUE. "Place one block as departure point.");
				$this->placeQueue[$sender->getName()] = true;
				break;
			case "id" : 		
				if($sender->isOP()){
          		$id = intval($args[1]);
          		if($args == null){
            	$sender->sendMessage(TextFormat::RED . "Usage /walktpid <block ID>");
          	}
          	if(isset($args[1])){
            	$this->getConfig()->set("walktpblock", $id);
            	$this->saveConfig();
            	$sender->sendMessage(TextFormat::GREEN . "[LEETWalkTp]" . TextFormat::AQUA . " block id Was Set To:" . TextFormat::BLUE . " $id.");
          	}
          	}
				break;
			case "help" :
				if (!$sender->hasPermission("walktp.command.help")) {
					$sender->sendMessage(new TranslationContainer(TextFormat::RED."%commands.generic.permission"));
					break;
				}
				$sender->sendMessage("/walktp set -" .TextFormat::BLUE. "Create walk teleport blocks .");
				$sender->sendMessage("/walktp id <block id> -" .TextFormat::BLUE. "Change default WalkTP block with another block.");
				$sender->sendMessage("/walktp edit -".TextFormat::BLUE. "Edit your walktp block.");				
				break;
#Thanks to vaivez66 for fixing small bug in /walktp edit command				
			case "edit" :
				if (!$sender->hasPermission("walktp.command.edit")) {
					$sender->sendMessage(new TranslationContainer(TextFormat::RED."%commands.generic.permission"));
					break;
				}
				if (isset($this->editmode[$sender->getName()])) {
					$sender->sendMessage(TextFormat::GREEN. "You are not in edit mode.");
					unset($this->editmode[$sender->getName()]);
				} else {
					$sender->sendMessage(TextFormat::GREEN. "You are in edit mode.");
					$this->editmode[$sender->getName()] = true;
				}
				break;
			default :
				return false;
		}
		return true;
	}
	public function onPlace(BlockPlaceEvent $event) {
		$walktpblock = $this->getConfig()->get("walktpblock");
		$player = $event->getPlayer();
		if (!isset($this->placeQueue[$player->getName()])) {
			return;
		}
		if ($this->placeQueue[$player->getName()] === true) {
			$block = $event->getBlock();
			if ($block->getId() !== $walktpblock) {
				return;
			}
			$this->placeQueue[$player->getName()] = "{$block->getX()}:{$block->getY()}:{$block->getZ()}:{$block->getLevel()->getFolderName()}";
			$player->sendMessage(TextFormat::BLUE."Place second block as Arrival point" );
		}
	}
	public function onTouch(PlayerInteractEvent $event) {
		$player = $event->getPlayer();
		if (!isset($this->placeQueue[$player->getName()])) {
			return;
		}
		if ($this->placeQueue[$player->getName()] !== true) {
			$block = $event->getBlock();
			$this->walktp[$this->placeQueue[$player->getName()]] = "{$block->getX()}:" . (string)($block->getY() + 1) . ":{$block->getZ()}:{$block->getLevel()->getFolderName()}";
			$player->sendMessage(TextFormat::GREEN."WalkTP has been set.");
			unset($this->placeQueue[$player->getName()]);
		}
	}
	public function onTouchMove(PlayerInteractEvent $event) {
		$walktpblock = $this->getConfig()->get("walktpblock");
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if ($block->getId() !== $walktpblock) {
			return;
		}
		if (isset($this->editmode[$player->getName()])) {
			return;
		}
		$pos = new Position($block->getX(), $block->getY(), $block->getZ(), $block->getLevel());
		if (!isset($this->walktp[$this->PosToString($pos)])) {
			return;
		}
		$player->teleport($this->StringToPos($this->walktp[$this->PosToString($pos)]));
	}
	public function onBreak(BlockBreakEvent $event) {
		$walktpblock = $this->getConfig()->get("walktpblock");
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if ($block->getId() !== $walktpblock) {
			return;
		}
		if (!isset($this->walktp["{$block->getX()}:{$block->getY()}:{$block->getZ()}:{$block->getLevel()->getFolderName()}"])) {
			return;
		}
		if (!$player->hasPermission("walktp.delete")){
			$event->setCancelled();
			$player->sendMessage(TextFormat::RED."You dont have permission to break this block.");
			return;
		}
		unset($this->walktp["{$block->getX()}:{$block->getY()}:{$block->getZ()}:{$block->getLevel()->getFolderName()}"]);
		$player->sendMessage( TextFormat::GREEN."Walktp has been removed.");
	}
	private function StringToPos($string) {
		$pos = explode(":", $string);
		return new Position($pos[0], $pos[1], $pos[2], $this->getServer()->getLevelByName($pos[3]));
	}
	private function PosToString(Position $pos) {
		return "{$pos->getX()}:{$pos->getY()}:{$pos->getZ()}:{$pos->getLevel()->getFolderName()}";
	}
	public function onMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer();
		$x=$player->x;
    	$y=$player->y;
    	$z=$player->z;
		if (!$player->hasPermission("walktp.use")) {
			return;
		}
		$pos = $player->getPosition();
		$pos->x = round($pos->getX());
		$pos->y = round($pos->getY()) - 1;
		$pos->z = round($pos->getZ());
		if (!isset($this->walktp[$this->PosToString($pos)])) {
			return;
		}
		$target = $this->StringToPos($this->walktp[$this->PosToString($pos)]);
		if ($target->getLevel() !== $player->getLevel()) {
			return;
		}
 		$player->getLevel()->addParticle(new ExplodeParticle(new Vector3($x-1, $y-1, $z)));
 		$player->getLevel()->addParticle(new ExplodeParticle(new Vector3($x, $y-1, $z-1)));
 		$player->getLevel()->addParticle(new ExplodeParticle(new Vector3($x+1, $y-1, $z)));
 		$player->getLevel()->addParticle(new ExplodeParticle(new Vector3($x, $y-1, $z+1)));
 		$player->getLevel()->addParticle(new ExplodeParticle(new Vector3($x, $y-1, $z)));
		$this->getServer()->getScheduler()->scheduleDelayedTask(new Cooldown($this, $player), 60 * 20); 		
	}
}
