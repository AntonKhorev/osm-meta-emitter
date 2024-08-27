<?php namespace OsmMetaEmitter\OgImage;

class CompositeTile {
	function __construct(
		private Scale $scale,
		private IntPixelCoordsBbox $window
	) {}

	function getImage(callable $fetchOsmTile): \GdImage {
		$size = $this->window->getSize();
		$image = imagecreatetruecolor($size->x, $size->y);
		$background_color = imagecolorallocate($image, 128, 128, 128);
		imagefilledrectangle($image, 0, 0, $size->x, $size->y, $background_color);
		foreach ($this->listOsmTilePlacements($this->scale) as $placement) {
			$osm_tile_data = $fetchOsmTile($placement->path);
			if ($osm_tile_data === null) continue;
			$osm_tile_image = imagecreatefromstring($osm_tile_data);
			imagecopy(
				$image, $osm_tile_image,
				$placement->offset->x, $placement->offset->y,
				0, 0,
				$this->scale->getTileSize(), $this->scale->getTileSize()
			);
		}
		return $image;
	}

	private function listOsmTilePlacements(Scale $scale): \Generator {
		$bbox = $scale->constrainTileIndexBboxToWorld(
			$scale->convertIntPixelCoordsBboxToTileIndexBbox($this->window)
		);
		foreach ($bbox->iterateOverTileIndexes() as $tile_index) {
			$tile_coords = $scale->convertTileIndexToIntPixelCoords($tile_index);
			$tile_offset = $this->window->getOffset($tile_coords);
			yield new OsmTilePlacement("$scale->zoom/$tile_index->x/$tile_index->y.png", $tile_offset);
		}
	}
}
