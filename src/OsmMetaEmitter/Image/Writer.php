<?php namespace OsmMetaEmitter\Image;

class Writer {
	function __construct(
		private HttpClient $client,
		private string $osm_tile_url,
		private IntPixelSize $image_size,
		private \OsmMetaEmitter\Graphics\CanvasFactory $canvas_factory
	) {}

	function respondWithNodeImage(\OsmMetaEmitter\Osm\Node $node, bool $crosshair): void {
		$scale = new Scale(16);
		$window = IntPixelCoordsBbox::fromCenterAndSize(
			$scale->convertNormalizedCoordsToFloatPixelCoords($node->getCenter()),
			$this->image_size
		);
		$composite_tile = new CompositeTile($this->canvas_factory, $scale, $window);

		$canvas = $composite_tile->getCanvas(
			fn(string $path) => $this->client->fetch($this->osm_tile_url . $path, 15)
		);

		if ($crosshair) $canvas->drawCrosshair();

		$canvas->drawPointMarker($this->image_size->x / 2, $this->image_size->y / 2, $node->visible);

		$canvas->outputImage();
	}

	function respondWithWayImage(\OsmMetaEmitter\Osm\Way $way, bool $crosshair): void {
		$scale = $this->getScaleForNormalizedCoordsBbox($way->getBbox());
		$window = IntPixelCoordsBbox::fromCenterAndSize(
			$scale->convertNormalizedCoordsToFloatPixelCoords($way->getCenter()),
			$this->image_size
		);
		$composite_tile = new CompositeTile($this->canvas_factory, $scale, $window);

		$canvas = $composite_tile->getCanvas(
			fn(string $path) => $this->client->fetch($this->osm_tile_url . $path, 15)
		);

		if ($crosshair) $canvas->drawCrosshair();

		$way_bbox = $scale->convertNormalizedCoordsBboxToFloatPixelCoordsBbox($way->getBbox());
		$min_size_to_draw_lines = 8;
		if (
			$way_bbox->getSize()->x >= $min_size_to_draw_lines ||
			$way_bbox->getSize()->y >= $min_size_to_draw_lines
		) {
			$canvas->drawPolyLine(
				array_map(
					fn($point) => $window->getFloatOffset(
						$scale->convertNormalizedCoordsToFloatPixelCoords($point)
					)->toArray(),
					iterator_to_array($way->points)
				)
			);
		} else {
			$canvas->drawPointMarker($this->image_size->x / 2, $this->image_size->y / 2, $way->visible);
		}

		$canvas->outputImage();
	}

	function respondWithRelationImage(\OsmMetaEmitter\Osm\Relation $relation, bool $crosshair): void {
		$scale = new Scale(16);
		$window = IntPixelCoordsBbox::fromCenterAndSize(
			$scale->convertNormalizedCoordsToFloatPixelCoords($relation->getCenter()),
			$this->image_size
		);
		$composite_tile = new CompositeTile($this->canvas_factory, $scale, $window);

		$canvas = $composite_tile->getCanvas(
			fn(string $path) => $this->client->fetch($this->osm_tile_url . $path, 15)
		);

		if ($crosshair) $canvas->drawCrosshair();

		$canvas->drawPointMarker($this->image_size->x / 2, $this->image_size->y / 2, $relation->visible);

		$canvas->outputImage();
	}

	private function getScaleForNormalizedCoordsBbox(\OsmMetaEmitter\Osm\NormalizedCoordsBbox $bbox, int $margin = 4): Scale {
		$size_to_fit_into = $this->image_size->withoutMargins(4);
		for ($zoom = 16; $zoom >= 0; $zoom--) {
			$scale = new Scale($zoom);
			$pixel_bbox = $scale->convertNormalizedCoordsBboxToFloatPixelCoordsBbox($bbox)->toInt();
			if ($pixel_bbox->getSize()->fitsInto($size_to_fit_into)) return $scale;
		}
		return new Scale(0);
	}
}
