<?php namespace OsmMetaEmitter\OsmElement;

class Node extends Element {
	function __construct(private NormalizedCoords $point) {}

	function getCenter(): NormalizedCoords {
		return $this->point;
	}

	function getBbox(): NormalizedCoordsBbox {
		return new NormalizedCoordsBbox($this->point, $this->point);
	}
}
