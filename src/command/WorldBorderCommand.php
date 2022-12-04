<?php

declare(strict_types=1);

namespace skh6075\WorldBorder\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use pocketmine\utils\TextFormat;
use skh6075\WorldBorder\Loader;

final class WorldBorderCommand extends Command implements PluginOwned{
	use PluginOwnedTrait;

	public function __construct(Loader $plugin){
		parent::__construct('wb', 'WorldBorder command');
		$this->setAliases(['월드보더', 'worldborder']);
		$this->setPermission('command.worldborder');
		$this->owningPlugin = $plugin;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender instanceof Player || !$this->testPermission($sender)){
			return false;
		}

		switch(array_shift($args) ?? 'x'){
			case 'create':
			case '생성':
				$selectedArea = $this->owningPlugin->getSelectedArea($sender);
				if($selectedArea === null || $selectedArea->getVolume() <= 0){
					$sender->sendMessage(TextFormat::RED . "First you need to select the border area.");
					return false;
				}

				if(count($args) < 1){
					$sender->sendMessage(TextFormat::YELLOW . "/wb create <name>");
					return false;
				}

				$this->owningPlugin->addWorldBorder($sender, $selectedArea, array_shift($args));
				break;
			case 'delete':
			case 'remove':
			case '삭제':
				if(count($args) < 1){
					$sender->sendMessage(TextFormat::YELLOW . "/wb remove <name> <worldName: option>");
					return false;
				}

				$name = array_shift($args);
				$worldName = $sender->getWorld()->getFolderName();
				if(count($args) > 0){
					$worldName = array_shift($args);
				}

				$this->owningPlugin->removeWorldBorder($sender, $worldName, $name);
				break;
			default:
				$sender->sendMessage(TextFormat::YELLOW . "/wb create <name>");
				$sender->sendMessage(TextFormat::YELLOW . "/wb remove <name> <worldName: option>");
				break;
		}

		return true;
	}
}