<?php namespace OsmMetaEmitter\Image;

class CompositeTile {
	function __construct(
		private \OsmMetaEmitter\Graphics\CanvasFactory $canvas_factory,
		private Scale $scale,
		private IntPixelCoordsBbox $window
	) {}

	function getCanvas(callable $fetchOsmTile): \OsmMetaEmitter\Graphics\Canvas { // TODO move arg to ctor
		$size = $this->window->getSize();
		$canvas = $this->canvas_factory->makeCanvas(
			$size->x, $size->y,
			new \OsmMetaEmitter\Graphics\Color("#808080")
		);

		foreach ($this->listOsmTilePlacements($this->scale) as $placement) {
			$osm_tile_data = $fetchOsmTile($placement->path);
			if ($osm_tile_data === null) continue;
			$canvas->pasteImage($osm_tile_data, $placement->offset->x, $placement->offset->y);
		}

		return $canvas;
	}

	private function listOsmTilePlacements(Scale $scale): \Generator {
		$bbox = $scale->constrainTileIndexBboxToWorld(
			$scale->convertIntPixelCoordsBboxToTileIndexBbox($this->window)
		);
		foreach ($bbox->iterateOverTileIndexes() as $tile_index) {
			$tile_coords = $scale->convertTileIndexToIntPixelCoords($tile_index);
			$tile_offset = $this->window->getIntOffset($tile_coords);
			yield new OsmTilePlacement("$scale->zoom/$tile_index->x/$tile_index->y.png", $tile_offset);
		}
	}
}
