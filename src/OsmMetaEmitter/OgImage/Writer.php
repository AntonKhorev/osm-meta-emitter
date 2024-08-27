<?php namespace OsmMetaEmitter\OgImage;

class Writer {
	function __construct(
		private HttpClient $client,
		private string $osm_tile_url,
		private IntPixelSize $image_size
	) {}

	function respondWithNodeImage(\OsmMetaEmitter\OsmElement\Node $node, bool $crosshair): void {
		$scale = new Scale(16);
		$window = IntPixelCoordsBbox::fromCenterAndSize(
			$scale->convertNormalizedCoordsToFloatPixelCoords($node->getCenter()),
			$this->image_size
		);
		$composite_tile = new CompositeTile($scale, $window);

		$image = $composite_tile->getImage(
			fn(string $path) => $this->client->fetch($this->osm_tile_url . $path, 15)
		);

		if ($crosshair) $this->drawCrosshair($image);

		$this->drawCenterPointMarker($image, $node->visible);

		header("Content-Type: image/png");
		imagepng($image);
	}

	function respondWithWayImage(\OsmMetaEmitter\OsmElement\Way $way, bool $crosshair): void {
		$scale = $this->getScaleForNormalizedCoordsBbox($way->getBbox());
		$window = IntPixelCoordsBbox::fromCenterAndSize(
			$scale->convertNormalizedCoordsToFloatPixelCoords($way->getCenter()),
			$this->image_size
		);
		$composite_tile = new CompositeTile($scale, $window);

		$image = $composite_tile->getImage(
			fn(string $path) => $this->client->fetch($this->osm_tile_url . $path, 15)
		);

		if ($crosshair) $this->drawCrosshair($image);

		$way_bbox = $scale->convertNormalizedCoordsBboxToFloatPixelCoordsBbox($way->getBbox());
		$min_size_to_draw_lines = 8;
		if (
			$way_bbox->getSize()->x >= $min_size_to_draw_lines ||
			$way_bbox->getSize()->y >= $min_size_to_draw_lines
		) {
			if ($way->visible) {
				$line_color = imagecolorallocate($image, 255, 98, 0);
				$rectangle = $window->getOffsetBbox($way_bbox->toInt());
				imagerectangle(
					$image,
					$rectangle->min->x, $rectangle->min->y,
					$rectangle->max->x, $rectangle->max->y,
					$line_color
				);
			} else {
				// TODO red color, but we don't have the shape yet
			}
		} else {
			$this->drawCenterPointMarker($image, $way->visible);
		}

		header("Content-Type: image/png");
		imagepng($image);
	}

	private function getScaleForNormalizedCoordsBbox(\OsmMetaEmitter\OsmElement\NormalizedCoordsBbox $bbox, int $margin = 4): Scale {
		$size_to_fit_into = $this->image_size->withoutMargins(4);
		for ($zoom = 16; $zoom >=0; $zoom--) {
			$scale = new Scale($zoom);
			$pixel_bbox = $scale->convertNormalizedCoordsBboxToFloatPixelCoordsBbox($bbox)->toInt();
			if ($pixel_bbox->getSize()->fitsInto($size_to_fit_into)) return $scale;
		}
		return new Scale(0);
	}

	private function drawCrosshair(\GdImage $image): void {
		$crosshair_color = imagecolorallocatealpha($image, 128, 128, 128, 64);
		imageline($image, $this->image_size->x / 2, 0, $this->image_size->x / 2, $this->image_size->y - 1, $crosshair_color);
		imageline($image, $this->image_size->x / 2 + 1, 0, $this->image_size->x / 2 + 1, $this->image_size->y - 1, $crosshair_color);
		imageline($image, 0, $this->image_size->y / 2, $this->image_size->x - 1, $this->image_size->y / 2, $crosshair_color);
		imageline($image, 0, $this->image_size->y / 2 + 1, $this->image_size->x - 1, $this->image_size->y / 2 + 1, $crosshair_color);
	}

	private function drawCenterPointMarker(\GdImage $image, bool $visible): void {
		if ($visible) {
			$marker_image = imagecreatefrompng("assets/node_marker.png");
		} else {
			$marker_image = imagecreatefrompng("assets/deleted_node_marker.png");
		}
		imagecopy(
			$image, $marker_image,
			$this->image_size->x / 2 - imagesx($marker_image) / 2 + 1,
			$this->image_size->y / 2 - imagesy($marker_image) / 2 + 1,
			0, 0,
			imagesx($marker_image), imagesy($marker_image)
		);
	}
}
