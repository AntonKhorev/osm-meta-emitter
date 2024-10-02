<?php namespace OsmMetaEmitter\Image;

class OsmTilePlacement {
	function __construct(
		public TileIndex $index,
		public IntPixelCoords $offset
	) {}
}
