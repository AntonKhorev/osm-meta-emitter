<?php namespace OsmMetaEmitter\Tile;

class LoadEntry {
	function __construct(
		public string $path,
		public ?string $etag = null
	) {}
}
