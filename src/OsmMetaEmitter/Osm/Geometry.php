<?php namespace OsmMetaEmitter\Osm;

abstract class Geometry {
	function getCenter(): NormalizedCoords {
		return $this->getBbox()->getCenter();
	}

	abstract function getBbox(): NormalizedCoordsBbox;
}
