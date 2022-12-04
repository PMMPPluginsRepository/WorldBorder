<?php

declare(strict_types=1);

namespace skh6075\WorldBorder;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Filesystem;
use pocketmine\utils\TextFormat;
use skh6075\WorldBorder\border\WorldBorder;
use skh6075\WorldBorder\command\WorldBorderCommand;
use Symfony\Component\Filesystem\Path;

final class Loader extends PluginBase implements Listener{
	/**
	 * @phpstan-var array<string, SelectedArea>
	 * @var SelectedArea[]
	 */
	private array $selectedArea = [];

	/**
	 * @phpstan-var array<string, array<string, WorldBorder>>
	 * @var WorldBorder[][]
	 */
	private array $db = [];

	/**
	 * @phpstan-var array<string, WorldBorder>
	 * @var WorldBorder[]
	 */
	private array $lastedCollideArea = [];

	private Item $borderItem;

	protected function onEnable() : void{
		if(file_exists($path = Path::join($this->getDataFolder(), "worldBorder.json"))){
			$data = json_decode(file_get_contents($path), true);
			foreach($data as $worldName => $datum){
				$this->db[$worldName] = [];
				foreach($datum as $borderData){
					$border = WorldBorder::jsonDeserialize($borderData);
					$this->db[$worldName][$border->getName()] = $border;
				}
			}
		}

		$this->borderItem = VanillaItems::GOLDEN_AXE();
		$this->getServer()->getCommandMap()->register(strtolower($this->getName()), new WorldBorderCommand($this));
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	protected function onDisable() : void{
		$data = [];
		foreach($this->db as $world => $borders){
			$data[$world] = [];
			foreach($borders as $border){
				$data[$world][$border->getName()] = $border->jsonSerialize();
			}
		}

		Filesystem::safeFilePutContents(Path::join($this->getDataFolder(), "worldBorder.json"), json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	}

	public function getSelectedArea(Player $player): ?SelectedArea{
		return $this->selectedArea[$player->getUniqueId()->getBytes()] ?? null;
	}

	public function addWorldBorder(Player $player, SelectedArea $area, string $name, bool $sessionDestroy = true): void{
		$worldName = $player->getWorld()->getFolderName();
		if(isset($this->db[$worldName][$name])){
			$player->sendMessage(TextFormat::RED . "A border with the same name as $name has been created in the current world.");
			return;
		}

		$this->db[$worldName][$name] = new WorldBorder($name, $worldName, $area->getMinPosition(), $area->getMaxPosition());
		if($sessionDestroy){
			unset($this->selectedArea[$player->getUniqueId()->getBytes()]);
		}

		$player->sendMessage(TextFormat::AQUA . "Created a border with name $name in the current world.");
	}

	public function removeWorldBorder(Player $player, string $worldName, string $name): void{
		if(!isset($this->db[$worldName][$name])){
			$player->sendMessage(TextFormat::RED . "Could not find border created with name $name in world $worldName");
			return;
		}

		unset($this->db[$worldName][$name]);
		$player->sendMessage(TextFormat::AQUA . "Successfully remove border created by name $name in world $worldName");
	}

	/** @priority MONITOR */
	public function onBlockBreakEvent(BlockBreakEvent $event): void{
		if(
			($player = $event->getPlayer())->hasPermission("wb.bypass") &&
			$event->getItem()->equals($this->borderItem, false, false)
		){
			$event->cancel();
			$result = ($this->selectedArea[$player->getUniqueId()->getBytes()] ??= new SelectedArea($player->getWorld()))->setFirstPosition($event->getBlock()->getPosition());
			$player->sendMessage(TextFormat::GREEN . "Selected the first border edge." . ($result->getVolume() > 0 ? " (Current Size: " . $result->getVolume() . ")" : ""));
		}
	}

	/** @priority MONITOR */
	public function onPlayerInteractEvent(PlayerInteractEvent $event): void{
		if(
			($player = $event->getPlayer())->hasPermission("wb.bypass") &&
			$event->getAction() === $event::RIGHT_CLICK_BLOCK &&
			$event->getItem()->equals($this->borderItem, false, false)
		){
			$event->cancel();
			$result = ($this->selectedArea[$player->getUniqueId()->getBytes()] ??= new SelectedArea($player->getWorld()))->setSecondPosition($event->getBlock()->getPosition());
			$player->sendMessage(TextFormat::GREEN . "Selected the second border edge." . ($result->getVolume() > 0 ? " (Current Size: " . $result->getVolume() . ")" : ""));
		}
	}

	/** @priority MONITOR */
	public function onPlayerMoveEvent(PlayerMoveEvent $event): void{
		if($event->isCancelled()){
			return;
		}

		$player = $event->getPlayer();
		$area = $this->lastedCollideArea[$player->getName()] ?? null;
		if($area !== null){
			if(!$area->collider($player)){
				unset($this->lastedCollideArea[$player->getName()]);
			}else{
//				$player->sendActionBarMessage("U now collide border is {$area->getName()}");
			}
		}else{
			foreach(($this->db[$player->getWorld()->getFolderName()] ?? []) as $border){
				if($border->collider($player)){
					$this->lastedCollideArea[$player->getName()] = $border;
//					$player->sendActionBarMessage("U now collide border is {$border->getName()}");
					break;
				}
			}
		}
	}
}