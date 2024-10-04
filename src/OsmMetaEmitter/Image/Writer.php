<?php namespace OsmMetaEmitter\Image;

class Writer {
	function __construct(
		private \OsmMetaEmitter\ClientCacheHandler $client_cache_handler,
		private \OsmMetaEmitter\Tile\Loader $tile_loader,
		private IntPixelSize $image_size,
		private \OsmMetaEmitter\Osm\MaxZoomAlgorithm $max_zoom_algorithm,
		private \OsmMetaEmitter\Graphics\CanvasFactory $canvas_factory,
		private bool $crosshair,
	) {}

	function respondWithElementImage(\OsmMetaEmitter\Osm\Element $element): void {
		$this->client_cache_handler->checkMainEtag($element->timestamp);
		if ($this->client_cache_handler->can_skip_tiles) {
			$this->client_cache_handler->sendNotModifiedHeaders();
			return;
		}

		$scale = $this->getScaleForElement($element);
		$window = IntPixelCoordsBbox::fromCenterAndSize(
			$scale->convertNormalizedCoordsToFloatPixelCoords($element->geometry->getCenter()),
			$this->image_size
		);
		$composite_tile = new CompositeTile(
			$this->tile_loader, $this->canvas_factory,
			$scale, $window,
			$this->client_cache_handler->tile_etags
		);

		$canvas = $composite_tile->getCanvas();
		if (!$canvas) {
			$this->client_cache_handler->sendNotModifiedHeaders();
			return;
		}

		if ($this->crosshair) $canvas->drawCrosshair();
		$this->drawGeometry($scale, $window, $canvas, $element->visible, $element->geometry);

		$this->client_cache_handler->sendEtagHeaders($element->timestamp, $composite_tile->etags);
		$canvas->outputImage();
	}

	private function getScaleForElement(\OsmMetaEmitter\Osm\Element $element, int $margin = 4): Scale {
		$bbox = $element->geometry->getBbox();
		$size_to_fit_into = $this->image_size->withoutMargins($margin);
		$max_zoom = $this->max_zoom_algorithm->getMaxZoomFromTags($element->tags);
		for ($zoom = $max_zoom; $zoom >= 0; $zoom--) {
			$scale = new Scale($zoom);
			$pixel_bbox = $scale->convertNormalizedCoordsBboxToFloatPixelCoordsBbox($bbox)->toInt();
			if ($pixel_bbox->getSize()->fitsInto($size_to_fit_into)) return $scale;
		}
		return new Scale(0);
	}

	private function drawGeometry(
		Scale $scale, IntPixelCoordsBbox $window,
		\OsmMetaEmitter\Graphics\Canvas $canvas,
		bool $visible,
		\OsmMetaEmitter\Osm\Geometry $geometry,
	): void {
		if ($geometry instanceof \OsmMetaEmitter\Osm\GeometryCollection) {
			foreach ($geometry as $sub_geometry) {
				$this->drawGeometry($scale, $window, $canvas, $visible, $sub_geometry);
			}
		} elseif ($geometry instanceof \OsmMetaEmitter\Osm\Point) {
			$this->drawPointMarker($scale, $window, $canvas, $visible, $geometry->coords);
		} elseif ($geometry instanceof \OsmMetaEmitter\Osm\LineString) {
			$min_size_to_draw_lines = 8;
			$line_bbox = $scale->convertNormalizedCoordsBboxToFloatPixelCoordsBbox($geometry->getBbox());
			if (
				$line_bbox->getSize()->x >= $min_size_to_draw_lines ||
				$line_bbox->getSize()->y >= $min_size_to_draw_lines
			) {
				$canvas->drawPolyLine(
					array_map(
						fn($point) => $window->getFloatOffset(
							$scale->convertNormalizedCoordsToFloatPixelCoords($point)
						)->toArray(),
						$geometry->coords_array
					)
				); // TODO visible state, although it's not yet supported for anything containing lines
			} else {
				$this->drawPointMarker($scale, $window, $canvas, $visible, $geometry->getCenter());
			}
		}
	}

	private function drawPointMarker(
		Scale $scale, IntPixelCoordsBbox $window,
		\OsmMetaEmitter\Graphics\Canvas $canvas,
		bool $visible,
		\OsmMetaEmitter\Osm\NormalizedCoords $point,
	): void {
		$offset = $window->getFloatOffset(
			$scale->convertNormalizedCoordsToFloatPixelCoords($point)
		);
		$canvas->drawPointMarker($offset->x, $offset->y, $visible);
	}
}
