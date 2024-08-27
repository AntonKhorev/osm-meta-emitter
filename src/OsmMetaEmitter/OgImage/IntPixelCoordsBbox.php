<?php namespace OsmMetaEmitter\OgImage;

class IntPixelCoordsBbox {
	function __construct(
		public IntPixelCoords $min,
		public IntPixelCoords $max
	) {}

	static function fromCenterAndSize(FloatPixelCoords $center, IntPixelSize $size): static {
		$min = new IntPixelCoords(
			round($center->x - $size->x / 2),
			round($center->y - $size->y / 2)
		);
		$max = new IntPixelCoords(
			$min->x + $size->x - 1,
			$min->y + $size->y - 1
		);
		return new static($min, $max);
	}

	function getSize(): IntPixelSize {
		return new IntPixelSize(
			$this->max->x - $this->min->x + 1,
			$this->max->y - $this->min->y + 1
		);
	}

	function getOffset(IntPixelCoords $coords): IntPixelCoords {
		return new IntPixelCoords(
			$coords->x - $this->min->x,
			$coords->y - $this->min->y
		);
	}
}
