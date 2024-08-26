<?php namespace OsmMetaEmitter\OgImage;

class OsmTilePlacement {
	function __construct(
		public string $path,
		public IntPixelCoords $offset
	) {}
}
