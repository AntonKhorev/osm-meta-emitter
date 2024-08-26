<?php namespace OsmMetaEmitter\OgImage;

use OsmMetaEmitter\OsmElement\NormalizedCoords;

class CompositeTile {
	function __construct(
		public int $size_x,
		public int $size_y,
		public IntPixelCoords $corner
	) {}

	static function fromZoomAndCenter(
		int $size_x,
		int $size_y,
		int $zoom,
		NormalizedCoords $center
	): static {
		$tile_pow = 8;
		$world_pow = $zoom + $tile_pow;
		$world_size = 1 << $world_pow;
		$corner = new IntPixelCoords(
			round($center->x * $world_size - $size_x / 2),
			round($center->y * $world_size - $size_y / 2)
		);
		return new static($size_x, $size_y, $corner);
	}

	function getBaseImage(callable $fetchOsmTile, int $zoom): \GdImage {
		$tile_pow = 8;
		$tile_size = 1 << $tile_pow;
		$image = imagecreatetruecolor($this->size_x, $this->size_y); // TODO fill with gray
		foreach ($this->listOsmTilePlacements($zoom) as $placement) {
			$osm_tile_data = $fetchOsmTile($placement->path);
			if ($osm_tile_data === null) continue;
			$osm_tile_image = imagecreatefromstring($osm_tile_data);
			imagecopy(
				$image, $osm_tile_image,
				$placement->offset->x, $placement->offset->y,
				0, 0,
				$tile_size, $tile_size
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

	private function listOsmTilePlacements(int $zoom) {
		$tile_pow = 8;
		$min_tile_index_x = $this->corner->x >> $tile_pow;
		$min_tile_index_y = $this->corner->y >> $tile_pow;
		$max_tile_index_x = ($this->corner->x + $this->size_x - 1) >> $tile_pow;
		$max_tile_index_y = ($this->corner->y + $this->size_y - 1) >> $tile_pow;
		$max_world_tile_index = (1 << $zoom) - 1;

		for ($iy = max($min_tile_index_y, 0); $iy <= min($max_tile_index_y, $max_world_tile_index); $iy++) {
			for ($ix = max($min_tile_index_x, 0); $ix <= min($max_tile_index_x, $max_world_tile_index); $ix++) {
				$tile_coords = new IntPixelCoords($ix << $tile_pow, $iy << $tile_pow);
				$tile_offset = $this->getOffsetIntPixelCoords($tile_coords);
				yield new OsmTilePlacement("$zoom/$ix/$iy.png", $tile_offset);
			}
		}
	}
}
