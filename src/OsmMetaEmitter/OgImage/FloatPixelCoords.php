<?php namespace OsmMetaEmitter\OgImage;

class FloatPixelCoords {
	function __construct(
		public float $x,
		public float $y
	) {}

	function floor(): IntPixelCoords {
		return new IntPixelCoords(
			floor($this->x),
			floor($this->y)
		);
	}

	function ceil(): IntPixelCoords {
		return new IntPixelCoords(
			ceil($this->x),
			ceil($this->y)
		);
	}
}
