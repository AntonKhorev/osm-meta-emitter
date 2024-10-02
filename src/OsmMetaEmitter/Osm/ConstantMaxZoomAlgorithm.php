<?php namespace OsmMetaEmitter\Osm;

class ConstantMaxZoomAlgorithm extends MaxZoomAlgorithm {
	function __construct(
		private int $max_zoom
	) {}

	function getMaxZoomFromTags(?object $tags): int {
		return $this->max_zoom;
	}
}
