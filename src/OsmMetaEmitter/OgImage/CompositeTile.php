<?php namespace OsmMetaEmitter\OgImage;

class CompositeTile {
	function __construct(
		public int $size_x,
		public int $size_y,
		public IntPixelCoords $corner
	) {}

	static function fromCenter(
		int $size_x,
		int $size_y,
		FloatPixelCoords $center
	): static {
		$corner = new IntPixelCoords(
			round($center->x - $size_x / 2),
			round($center->y - $size_y / 2)
		);
		return new static($size_x, $size_y, $corner);
	}

	function getMinCorner(): IntPixelCoords {
		return $this->corner;
	}

	function getMaxCorner(): IntPixelCoords {
		return new IntPixelCoords(
			$this->corner->x + $this->size_x - 1,
			$this->corner->y + $this->size_y - 1
		);
	}

	function getBaseImage(callable $fetchOsmTile, Scale $scale): \GdImage {
		$image = imagecreatetruecolor($this->size_x, $this->size_y);
		$background_color = imagecolorallocate($image, 128, 128, 128);
		imagefilledrectangle($image, 0, 0, $this->size_x, $this->size_y, $background_color);
		foreach ($this->listOsmTilePlacements($scale) as $placement) {
			$osm_tile_data = $fetchOsmTile($placement->path);
			if ($osm_tile_data === null) continue;
			$osm_tile_image = imagecreatefromstring($osm_tile_data);
			imagecopy(
				$image, $osm_tile_image,
				$placement->offset->x, $placement->offset->y,
				0, 0,
				$scale->getTileSize(), $scale->getTileSize()
			);
		}
		return $image;
	}

	function getOffsetIntPixelCoords(IntPixelCoords $coords): IntPixelCoords {
		return new IntPixelCoords(
			$coords->x - $this->corner->x,
			$coords->y - $this->corner->y
		);
	}

	private function listOsmTilePlacements(Scale $scale): \Generator {
		$bbox = $scale->constrainTileIndexBboxToWorld(
			new TileIndexBbox(
				$scale->convertIntPixelCoordsToTileIndex($this->getMinCorner()),
				$scale->convertIntPixelCoordsToTileIndex($this->getMaxCorner())
			)
		);
		foreach ($bbox->iterateOverTileIndexes() as $tile_index) {
			$tile_coords = $scale->convertTileIndexToIntPixelCoords($tile_index);
			$tile_offset = $this->getOffsetIntPixelCoords($tile_coords);
			yield new OsmTilePlacement("$scale->zoom/$tile_index->x/$tile_index->y.png", $tile_offset);
		}
	}
}
