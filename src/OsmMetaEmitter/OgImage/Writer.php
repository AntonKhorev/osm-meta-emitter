<?php namespace OsmMetaEmitter\OgImage;

class Writer {
	function __construct(
		private HttpClient $client,
		private string $osm_tile_url
	) {}

	function respondWithNodeImage(\OsmMetaEmitter\OsmElement\Node $node, bool $crosshair): void {
		$tile_pow = 8;
		$tile_size = 1 << $tile_pow;

		$zoom = 16;
		$composite_tile = CompositeTile::fromZoomAndCenter($tile_size, $tile_size, $zoom, $node->getCenter());

		$image = $composite_tile->getBaseImage(
			fn(string $path) => $this->client->fetch($this->osm_tile_url . $path),
			$zoom
		);

		if ($crosshair) {
			$crosshair_color = imagecolorallocatealpha($image, 128, 128, 128, 64);
			imageline($image, $tile_size / 2, 0, $tile_size / 2, $tile_size - 1, $crosshair_color);
			imageline($image, $tile_size / 2 + 1, 0, $tile_size / 2 + 1, $tile_size - 1, $crosshair_color);
			imageline($image, 0, $tile_size / 2, $tile_size - 1, $tile_size / 2, $crosshair_color);
			imageline($image, 0, $tile_size / 2 + 1, $tile_size - 1, $tile_size / 2 + 1, $crosshair_color);
		}

		if ($node->visible) {
			$marker_image = imagecreatefrompng("assets/node_marker.png");
		} else {
			$marker_image = imagecreatefrompng("assets/deleted_node_marker.png");
		}
		imagecopy(
			$image, $marker_image,
			$tile_size / 2 - imagesx($marker_image) / 2 + 1, $tile_size / 2 - imagesy($marker_image) / 2 + 1,
			0, 0,
			imagesx($marker_image), imagesy($marker_image)
		);

		header("Content-Type: image/png");
		imagepng($image);
	}
}
