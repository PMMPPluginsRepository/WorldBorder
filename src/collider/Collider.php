<?php

declare(strict_types=1);

namespace skh6075\WorldBorder\collider;

use InvalidArgumentException;
use pocketmine\player\Player;

class Collider{
	public int $minX;
	public int $minZ;
	public int $maxX;
	public int $maxZ;
	public string $world;

	public function __construct(int $minX, int $minZ, int $maxX, int $maxZ, string $world){
		if($minX > $maxX){
			throw new InvalidArgumentException("minX $minX is larger than maxX $maxX");
		}
		if($minZ > $maxZ){
			throw new InvalidArgumentException("minZ $minZ is larger than maxZ $maxZ");
		}
		$this->minX = $minX;
		$this->minZ = $minZ;
		$this->maxX = $maxX;
		$this->maxZ = $maxZ;
		$this->world = $world;
	}

	public function isInside(Player $inside) : bool{
		$inside = $inside->getPosition();
		if($this->world !== strtolower($inside->getWorld()->getFolderName())){
			return false;
		}

		return ($inside->getX() >= $this->minX &&
			$inside->getX() <= $this->maxX &&
			$inside->getZ() >= $this->minZ &&
			$inside->getZ() <= $this->maxZ);
	}
}