<?php namespace OsmMetaEmitter\Osm;

class ConstantMaxZoomAlgorithm extends MaxZoomAlgorithm {
	function __construct(
		private int $max_zoom
	) {}

	function getMaxZoomFromTags(array $tags): int {
		return $this->max_zoom;
	}
}
