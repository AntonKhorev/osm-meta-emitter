<?php namespace OsmMetaEmitter\OgImage;

class FloatPixelCoordsBbox {
	function __construct(
		public FloatPixelCoords $min,
		public FloatPixelCoords $max
	) {}

	function toInt(): IntPixelCoordsBbox {
		return new IntPixelCoordsBbox(
			$this->min->floor(),
			$this->max->ceil()
		);
	}
}
