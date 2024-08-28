<?php namespace OsmMetaEmitter\Osm;

abstract class Element {
	public bool $visible = true;

	abstract function getCenter(): NormalizedCoords;
	abstract function getBbox(): NormalizedCoordsBbox;
}
