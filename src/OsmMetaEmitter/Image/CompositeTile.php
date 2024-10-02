<?php namespace OsmMetaEmitter\Image;

class CompositeTile {
	public ?array $etags = null;

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

		$this->etags = [];
		foreach ($this->listOsmTilePlacements() as $placement) {
			$path = $this->scale->zoom . "/" . $placement->index->x . "/" . $placement->index->y . ".png";
			$osm_tile_response = $fetchOsmTile($path);
			if ($osm_tile_response->body === null) {
				$this->etags = null;
				continue;
			}
			if ($this->etags !== null) {
				if ($osm_tile_response->etag === null) {
					$this->etags = null;
				} else {
					$this->etags[] = $osm_tile_response->etag;
				}
			}
			$canvas->pasteImage($osm_tile_response->body, $placement->offset->x, $placement->offset->y);
		}

		return $canvas;
	}

	private function listOsmTilePlacements(): \Generator {
		$bbox = $this->scale->constrainTileIndexBboxToWorld(
			$this->scale->convertIntPixelCoordsBboxToTileIndexBbox($this->window)
		);
		foreach ($bbox->iterateOverTileIndexes() as $tile_index) {
			$tile_coords = $this->scale->convertTileIndexToIntPixelCoords($tile_index);
			$tile_offset = $this->window->getIntOffset($tile_coords);
			yield new OsmTilePlacement($tile_index, $tile_offset);
		}
	}
}
