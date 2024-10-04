<?php namespace OsmMetaEmitter\Image;

class CompositeTile {
	function __construct(
		private \OsmMetaEmitter\Tile\Loader $loader,
		private \OsmMetaEmitter\Graphics\CanvasFactory $canvas_factory,
		private Scale $scale,
		private IntPixelCoordsBbox $window,
		public ?array $etags
	) {}

	function getCanvas(): ?\OsmMetaEmitter\Graphics\Canvas {
		$bbox = $this->scale->constrainTileIndexBboxToWorld(
			$this->scale->convertIntPixelCoordsBboxToTileIndexBbox($this->window)
		);
		$tile_paths = [];
		$tile_offsets = [];
		foreach ($bbox->iterateOverTileIndexes() as $tile_index) {
			$tile_coords = $this->scale->convertTileIndexToIntPixelCoords($tile_index);
			$tile_offsets[] = $this->window->getIntOffset($tile_coords);
			$tile_paths[] = $this->scale->zoom . "/" . $tile_index->x . "/" . $tile_index->y . ".png";
		}

		if ($this->etags !== null && count($this->etags) == count($tile_paths)) {
			$load_entries = [];
			foreach ($tile_paths as $i => $path) {
				$load_entries[] = new \OsmMetaEmitter\Tile\LoadEntry($path, $this->etags[$i]);
			}
		} else {
			$load_entries = array_map(fn($path) => new \OsmMetaEmitter\Tile\LoadEntry($path), $tile_paths);
		}

		$canvas = null;
		$this->loader->load(function ($i, $body) use (&$canvas, $tile_offsets) {
			if (!$canvas) {
				$size = $this->window->getSize();
				$canvas = $this->canvas_factory->makeCanvas(
					$size->x, $size->y,
					new \OsmMetaEmitter\Graphics\Color("#808080")
				);
			}
			$canvas->pasteImage($body, $tile_offsets[$i]->x, $tile_offsets[$i]->y);
		}, ...$load_entries);

		$got_all_etags = true;
		foreach ($load_entries as $load_entry) {
			if ($load_entry->etag === null) {
				$got_all_etags = false;
				break;
			}
		}
		if ($got_all_etags) {
			$this->etags = array_map(fn($load_entry) => $load_entry->etag, $load_entries);
		} else {
			$this->etags = null;
		}

		return $canvas;
	}
}
