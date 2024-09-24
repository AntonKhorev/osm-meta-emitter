<?php namespace OsmMetaEmitter\Osm;

class Way extends Element {
	function __construct(public NormalizedCoordsList $points) {} // TODO throw if empty list

	function getCenter(): NormalizedCoords {
		return $this->getBbox()->getCenter();
	}

	function getBbox(): NormalizedCoordsBbox {
		return $this->points->getBbox()->reify();
	}
}
