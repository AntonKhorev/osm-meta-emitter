<?php namespace OsmMetaEmitter\OgImage;

class FloatPixelCoords {
	function __construct(
		public float $x,
		public float $y
	) {}

	function toInt(): IntPixelCoords {
		return new IntPixelCoords(
			round($this->x),
			round($this->y)
		);
	}

	function toIntForMin(): IntPixelCoords {
		return new IntPixelCoords(
			floor($this->x),
			floor($this->y)
		);
	}

	function toIntForMax(): IntPixelCoords {
		return new IntPixelCoords(
			ceil($this->x),
			ceil($this->y)
		);
	}
}
