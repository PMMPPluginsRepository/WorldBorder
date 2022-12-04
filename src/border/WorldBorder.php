<?php

declare(strict_types=1);

namespace skh6075\WorldBorder\border;

use JetBrains\PhpStorm\Pure;
use JsonSerializable;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use skh6075\WorldBorder\collider\Collider;

final class WorldBorder implements JsonSerializable{
	private Collider $collider;

	public function __construct(
		private string $name,
		private string $world,
		private Vector3 $minPos,
		private Vector3 $maxPos
	){
		$this->collider = new Collider(
			minX: $this->minPos->getFloorX(),
			minZ: $this->minPos->getFloorZ(),
			maxX: $this->maxPos->getFloorX(),
			maxZ: $this->maxPos->getFloorZ(),
			world: $this->world);
	}

	public static function jsonDeserialize(array $data): WorldBorder{
		return new WorldBorder(
			name: (string) $data['name'],
			world: (string) $data['world'],
			minPos: new Vector3(...array_map('intval', $data['minPos'])),
			maxPos: new Vector3(...array_map('intval', $data['maxPos'])));
	}

	public function jsonSerialize(): array{
		return [
			'name' => $this->name,
			'world' => $this->world,
			'minPos' => [
				$this->minPos->getFloorX(),
				$this->minPos->getFloorY(),
				$this->minPos->getFloorZ()
			],
			'maxPos' => [
				$this->maxPos->getFloorX(),
				$this->maxPos->getFloorY(),
				$this->maxPos->getFloorZ()
			]
		];
	}

	public function collider(Player $player): bool{
		if($this->collider->isInside($player) || $player->hasPermission("wb.bypass")){
			return true;
		}

		$center = $this->getCenter($player->getPosition()->getFloorY());
		$deltaX = $center->x - $player->getLocation()->x;
		$deltaZ = $center->z - $player->getLocation()->z;
		$player->knockBack($deltaX, $deltaZ, 5.0);
		$player->sendActionBarMessage(TextFormat::RED . "You can't leave the designated area");
		return false;
	}

	#[Pure] public function getCenter(int $y) : Vector3{
		$xSize = $this->getMaxPos()->getFloorX() - $this->getMinPos()->getFloorX();
		$zSize = $this->getMaxPos()->getFloorZ() - $this->getMinPos()->getFloorZ();
		$x = $this->getMinPos()->getFloorX() + ($xSize / 2);
		$z = $this->getMinPos()->getFloorZ() + ($zSize / 2);
		return new Vector3($x, $y + 1, $z);
	}

	public function getName(): string{ return $this->name; }

	public function getWorld(): string{ return $this->world; }

	public function getMinPos(): Vector3{ return $this->minPos; }

	public function getMaxPos(): Vector3{ return $this->maxPos; }
}