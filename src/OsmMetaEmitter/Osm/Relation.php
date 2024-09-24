<?php namespace OsmMetaEmitter\Osm;

class Relation extends Element {
	function __construct(
		public NormalizedCoordsList $points,
		public NormalizedCoordsListList $lines
	) {}

	function getCenter(): NormalizedCoords {
		return $this->getBbox()->getCenter();
	}

	function getBbox(): NormalizedCoordsBbox {
		return $this->points->getBbox()->include($this->lines->getBbox())->reify();
	}
}
