<?php namespace OsmMetaEmitter\Osm;

class Point extends Geometry {
	function __construct(
		public NormalizedCoords $coords
	) {}

	function getCenter(): NormalizedCoords {
		return $this->coords;
	}

	function getBbox(): NormalizedCoordsBbox {
		return new NormalizedCoordsBbox($this->coords, $this->coords);
	}
}
