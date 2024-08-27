<?php namespace OsmMetaEmitter\OsmElement;

class NormalizedCoordsBbox {
	function __construct(
		public NormalizedCoords $min,
		public NormalizedCoords $max
	) {}

	function getCenter(): NormalizedCoords {
		return new NormalizedCoords(
			($this->min->x + $this->max->x) / 2,
			($this->min->y + $this->max->y) / 2
		);
	}
}
