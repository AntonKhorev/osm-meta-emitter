<?php namespace OsmMetaEmitter\OgImage;

use OsmMetaEmitter\OsmElement\NormalizedCoords;
use OsmMetaEmitter\OsmElement\NormalizedCoordsBbox;

class Scale {
	function __construct(
		public int $zoom,
		public int $tile_pow = 8
	) {}

	function getWorldSize(): int {
		$world_pow = $this->zoom + $this->tile_pow;
		return 1 << $world_pow;
	}

	function getTileSize(): int {
		return 1 << $this->tile_pow;
	}

	function getMaxTileIndex(): int {
		return (1 << $this->zoom) - 1;
	}

	function convertNormalizedCoordsToFloatPixelCoords(NormalizedCoords $coords): FloatPixelCoords {
		return new FloatPixelCoords(
			$coords->x * $this->getWorldSize(),
			$coords->y * $this->getWorldSize()
		);
	}

	function convertNormalizedCoordsBboxToFloatPixelCoordBbox(NormalizedCoordsBbox $bbox): FloatPixelCoordsBbox {
		return new FloatPixelCoordsBbox(
			$this->convertNormalizedCoordsToFloatPixelCoords($bbox->min),
			$this->convertNormalizedCoordsToFloatPixelCoords($bbox->max)
		);
	}

	function convertIntPixelCoordsToTileIndex(IntPixelCoords $coords): TileIndex {
		return new TileIndex(
			$coords->x >> $this->tile_pow,
			$coords->y >> $this->tile_pow
		);
	}

	function convertTileIndexToIntPixelCoords(TileIndex $tile_index): IntPixelCoords {
		return new IntPixelCoords(
			$tile_index->x << $this->tile_pow,
			$tile_index->y << $this->tile_pow
		);
	}

	function constrainTileIndexBboxToWorld(TileIndexBbox $bbox): TileIndexBbox {
		return new TileIndexBbox(
			new TileIndex(
				max($bbox->min->x, 0),
				max($bbox->min->y, 0)
			),
			new TileIndex(
				min($bbox->max->x, $this->getMaxTileIndex()),
				min($bbox->max->y, $this->getMaxTileIndex())
			),
		);
	}
}
