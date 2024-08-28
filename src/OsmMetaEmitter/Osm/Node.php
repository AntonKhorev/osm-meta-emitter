<?php namespace OsmMetaEmitter\Osm;

class Node extends Element {
	function __construct(public NormalizedCoords $point) {}

	function getCenter(): NormalizedCoords {
		return $this->point;
	}

	function getBbox(): NormalizedCoordsBbox {
		return new NormalizedCoordsBbox($this->point, $this->point);
	}
}
