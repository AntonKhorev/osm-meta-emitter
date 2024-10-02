<?php namespace OsmMetaEmitter\Osm;

abstract class MaxZoomAlgorithm {
	abstract function getMaxZoomFromTags(array $tags): int;
}
