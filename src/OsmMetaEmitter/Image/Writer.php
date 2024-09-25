<?php namespace OsmMetaEmitter\Image;

class Writer {
	function __construct(
		private HttpClient $client,
		private string $osm_tile_url,
		private IntPixelSize $image_size,
		private \OsmMetaEmitter\Graphics\CanvasFactory $canvas_factory
	) {}

	function respondWithElementImage(\OsmMetaEmitter\Osm\Element $element, bool $crosshair): void {
		$scale = $this->getScaleForNormalizedCoordsBbox($element->getBbox());
		$window = IntPixelCoordsBbox::fromCenterAndSize(
			$scale->convertNormalizedCoordsToFloatPixelCoords($element->getCenter()),
			$this->image_size
		);
		$composite_tile = new CompositeTile($this->canvas_factory, $scale, $window);

		$canvas = $composite_tile->getCanvas(
			fn(string $path) => $this->client->fetch($this->osm_tile_url . $path, 15)
		);

		if ($crosshair) $canvas->drawCrosshair();

		foreach ($element->points as $point) {
			$this->drawPointMarker($scale, $window, $canvas, $point, $element->visible);
		}

		$min_size_to_draw_lines = 8;
		foreach ($element->lines as $line) {
			$line_bbox = $scale->convertNormalizedCoordsBboxToFloatPixelCoordsBbox($line->getBbox()->reify());
			if (
				$line_bbox->getSize()->x >= $min_size_to_draw_lines ||
				$line_bbox->getSize()->y >= $min_size_to_draw_lines
			) {
				$canvas->drawPolyLine(
					array_map(
						fn($point) => $window->getFloatOffset(
							$scale->convertNormalizedCoordsToFloatPixelCoords($point)
						)->toArray(),
						iterator_to_array($line)
					)
				); // TODO visible state, although it's not yet supported for anything containing lines
			} else {
				$this->drawPointMarker($scale, $window, $canvas, $line->getBbox()->reify()->getCenter(), $element->visible);
			}
		}

		$canvas->outputImage();
	}

	private function getScaleForNormalizedCoordsBbox(\OsmMetaEmitter\Osm\NormalizedCoordsBbox $bbox, int $margin = 4): Scale {
		$size_to_fit_into = $this->image_size->withoutMargins($margin);
		for ($zoom = 16; $zoom >= 0; $zoom--) {
			$scale = new Scale($zoom);
			$pixel_bbox = $scale->convertNormalizedCoordsBboxToFloatPixelCoordsBbox($bbox)->toInt();
			if ($pixel_bbox->getSize()->fitsInto($size_to_fit_into)) return $scale;
		}
		return new Scale(0);
	}

	private function drawPointMarker(
		Scale $scale, IntPixelCoordsBbox $window,
		\OsmMetaEmitter\Graphics\Canvas $canvas,
		\OsmMetaEmitter\Osm\NormalizedCoords $point,
		bool $visible
	): void {
		$offset = $window->getFloatOffset(
			$scale->convertNormalizedCoordsToFloatPixelCoords($point)
		);
		$canvas->drawPointMarker($offset->x, $offset->y, $visible);
	}
}
