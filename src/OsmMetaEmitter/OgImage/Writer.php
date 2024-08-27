<?php namespace OsmMetaEmitter\OgImage;

class Writer {
	function __construct(
		private HttpClient $client,
		private string $osm_tile_url,
		private IntPixelSize $image_size
	) {}

	function respondWithNodeImage(\OsmMetaEmitter\OsmElement\Node $node, bool $crosshair): void {
		$scale = new Scale(16);
		$composite_tile = CompositeTile::fromCenter(
			$this->image_size,
			$scale->convertNormalizedCoordsToFloatPixelCoords($node->getCenter())
		);
		$image = $composite_tile->getBaseImage(
			fn(string $path) => $this->client->fetch($this->osm_tile_url . $path, 15),
			$scale
		);

		if ($crosshair) $this->drawCrosshair($image);
		$this->drawCenterPointMarker($image, $node->visible);

		header("Content-Type: image/png");
		imagepng($image);
	}

	function respondWithWayImage(\OsmMetaEmitter\OsmElement\Way $way, bool $crosshair): void {
		$scale = $this->getScaleForNormalizedCoordsBbox($way->getBbox());
		$composite_tile = CompositeTile::fromCenter(
			$this->image_size,
			$scale->convertNormalizedCoordsToFloatPixelCoords($way->getCenter())
		);
		$image = $composite_tile->getBaseImage(
			fn(string $path) => $this->client->fetch($this->osm_tile_url . $path, 15),
			$scale
		);

		if ($crosshair) $this->drawCrosshair($image);
		$this->drawCenterPointMarker($image, $way->visible); // TODO actually draw way if it's large enough

		header("Content-Type: image/png");
		imagepng($image);
	}

	private function getScaleForNormalizedCoordsBbox(\OsmMetaEmitter\OsmElement\NormalizedCoordsBbox $bbox, int $margin = 4): Scale {
		$size_to_fit_into = $this->image_size->withoutMargins(4);
		for ($zoom = 16; $zoom >=0; $zoom--) {
			$scale = new Scale($zoom);
			$pixel_bbox = $scale->convertNormalizedCoordsBboxToFloatPixelCoordBbox($bbox)->toInt();
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
