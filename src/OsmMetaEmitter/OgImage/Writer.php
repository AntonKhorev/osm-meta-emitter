<?php namespace OsmMetaEmitter\OgImage;

class Writer {
	function __construct(
		private HttpClient $client,
		private string $osm_tile_url,
		private int $image_size_x,
		private int $image_size_y
	) {}

	function respondWithNodeImage(\OsmMetaEmitter\OsmElement\Node $node, bool $crosshair): void {
		$scale = new Scale(16);
		$composite_tile = CompositeTile::fromCenter(
			$this->image_size_x, $this->image_size_y,
			$scale->convertNormalizedCoordsToFloatPixelCoords($node->getCenter())
		);
		$image = $composite_tile->getBaseImage(
			fn(string $path) => $this->client->fetch($this->osm_tile_url . $path),
			$scale
		);

		if ($crosshair) {
			$crosshair_color = imagecolorallocatealpha($image, 128, 128, 128, 64);
			imageline($image, $this->image_size_x / 2, 0, $this->image_size_x / 2, $this->image_size_y - 1, $crosshair_color);
			imageline($image, $this->image_size_x / 2 + 1, 0, $this->image_size_x / 2 + 1, $this->image_size_y - 1, $crosshair_color);
			imageline($image, 0, $this->image_size_y / 2, $this->image_size_x - 1, $this->image_size_y / 2, $crosshair_color);
			imageline($image, 0, $this->image_size_y / 2 + 1, $this->image_size_x - 1, $this->image_size_y / 2 + 1, $crosshair_color);
		}

		if ($node->visible) {
			$marker_image = imagecreatefrompng("assets/node_marker.png");
		} else {
			$marker_image = imagecreatefrompng("assets/deleted_node_marker.png");
		}
		imagecopy(
			$image, $marker_image,
			$this->image_size_x / 2 - imagesx($marker_image) / 2 + 1, $this->image_size_y / 2 - imagesy($marker_image) / 2 + 1,
			0, 0,
			imagesx($marker_image), imagesy($marker_image)
		);

		header("Content-Type: image/png");
		imagepng($image);
	}
}
