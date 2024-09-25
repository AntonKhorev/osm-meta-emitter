<?php namespace OsmMetaEmitter\Osm;

class Element {
	public bool $visible = true;

	function __construct(
		public Geometry $geometry
	) {}
}
