<?php

declare(strict_types=1);

namespace skh6075\WorldBorder;

use JetBrains\PhpStorm\Pure;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;

final class SelectedArea{
	private Vector3 $firstPos;

	private Vector3 $secondPos;

	public function __construct(private World $world){
		$this->firstPos = new Vector3(0, -1, 0);
		$this->secondPos = new Vector3(0, -1, 0);
	}

	public function setFirstPosition(Position $position): self{
		if($this->world !== $position->world){
			return $this;
		}

		$this->firstPos = $position->asVector3();

		return $this;
	}

	public function setSecondPosition(Position $position): self{
		if($this->world !== $position->world){
			return $this;
		}

		$this->secondPos = $position->asVector3();

		return $this;
	}

	#[Pure] public function getMinPosition() : Vector3{
		return new Vector3(
			min($this->firstPos->x, $this->secondPos->x),
			min($this->firstPos->y, $this->secondPos->y),
			min($this->firstPos->z, $this->secondPos->z));
	}

	#[Pure] public function getMaxPosition() : Vector3{
		return new Vector3(
			max($this->firstPos->x, $this->secondPos->x),
			max($this->firstPos->y, $this->secondPos->y),
			max($this->firstPos->z, $this->secondPos->z));
	}

	public function getFirstPosition() : Vector3{
		return $this->firstPos->asVector3();
	}

	public function getSecondPosition() : Vector3{
		return $this->secondPos->asVector3();
	}

	#[Pure] public function getVolume() : int{
		if($this->firstPos->getFloorY() < 0 || $this->secondPos->getFloorY() < 0){
			return 0;
		}

		$min = $this->getMinPosition();
		$max = $this->getMaxPosition();
		return ($max->x - $min->x + 1) * ($max->y - $min->y + 1) * ($max->z - $min->z + 1);
	}
}