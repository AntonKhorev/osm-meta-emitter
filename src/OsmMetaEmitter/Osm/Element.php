<?php namespace OsmMetaEmitter\Osm;

class Element {
	public const DEFAULT_MAX_ZOOM = 16;
	public bool $visible = true;

	function __construct(
		public Geometry $geometry,
		public int $max_zoom = self::DEFAULT_MAX_ZOOM
	) {}
}
