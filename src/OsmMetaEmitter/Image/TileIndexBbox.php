<?php namespace OsmMetaEmitter\Image;

class TileIndexBbox {
	function __construct(
		public TileIndex $min,
		public TileIndex $max
	) {}

	function iterateOverTileIndexes(): \Generator {
		for ($y = $this->min->y; $y <= $this->max->y; $y++) {
		for ($x = $this->min->x; $x <= $this->max->x; $x++) {
			yield new TileIndex($x, $y);
		}
		}
	}
}
