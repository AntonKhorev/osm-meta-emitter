<?php namespace OsmMetaEmitter\OgImage;

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
			$this->min->floor(),
			$this->max->ceil()
		);
	}
}
