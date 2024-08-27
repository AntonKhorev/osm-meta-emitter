<?php namespace OsmMetaEmitter\OgImage;

class IntPixelCoordsBbox {
	function __construct(
		public IntPixelCoords $min,
		public IntPixelCoords $max
	) {}

	function getSize(): IntPixelSize {
		return new IntPixelSize(
			$this->max->x - $this->min->x,
			$this->max->y - $this->min->y
		);
	}
}
