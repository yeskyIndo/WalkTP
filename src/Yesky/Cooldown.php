<?php
namespace Yesky\WalkTP;


use pocketmine\scheduler\PluginTask;
use pocketmine\Player;

class Cooldown extends PluginTask {

    public function __construct(Main $plugin, Player $player) {
        parent::__construct($plugin);
        $this->plugin = $plugin;
        $this->player = $player;
    }

    public function onRun($tick) {
        //do what you want to happen at the end of the cooldown here!
 	      	$player->getInventory()->addItem(Item::get(280, 0, 1));
				$player->getInventory()->addItem(Item::get(271, 0, 1));
				$player->getInventory()->addItem(Item::get(351, 8, 64));
				$player->teleport($target);
    }
}
