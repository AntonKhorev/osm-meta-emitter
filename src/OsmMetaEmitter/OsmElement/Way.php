<?php namespace OsmMetaEmitter\OsmElement;

class Way extends Element {
	function __construct(private NormalizedCoordsList $points) {} // TODO throw if empty list

	function getCenter(): NormalizedCoords {
		return $this->getBbox()->getCenter();
	}

	function getBbox(): NormalizedCoordsBbox {
		return $this->points->getBbox();
	}
}
