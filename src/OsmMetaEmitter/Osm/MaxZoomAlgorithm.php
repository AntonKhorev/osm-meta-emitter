<?php namespace OsmMetaEmitter\Osm;

abstract class MaxZoomAlgorithm {
	abstract function getMaxZoomFromTags(?object $tags): int;
}
