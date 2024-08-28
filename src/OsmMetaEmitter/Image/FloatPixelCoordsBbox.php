<?php namespace OsmMetaEmitter\Image;

class FloatPixelCoordsBbox {
	function __construct(
		public FloatPixelCoords $min,
		public FloatPixelCoords $max
	) {}

	function getSize(): FloatPixelSize {
		return new FloatPixelSize(
			$this->max->x - $this->min->x,
			$this->max->y - $this->min->y
		);
	}

	function toInt(): IntPixelCoordsBbox {
		return new IntPixelCoordsBbox(
			$this->min->toIntForMin(),
			$this->max->toIntForMax()
		);
	}
}
