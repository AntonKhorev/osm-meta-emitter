<?php namespace OsmMetaEmitter\Osm;

class Element {
	public bool $visible = true;
	public object $tags;

	function __construct(
		public Geometry $geometry,
		?object $tags
	) {
		$this->tags = $tags ?? new \stdClass;
	}
}
