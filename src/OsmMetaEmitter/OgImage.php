<?php namespace OsmMetaEmitter;

class OgImage {
	function __construct(
		private HttpClient $client,
		private string $osm_tile_url
	) {}

	function respondWithNodeImage(OsmElement\Node $node, bool $crosshair): void {
		$tile_pow = 8;
		$tile_size = 1 << $tile_pow;
		$tile_mask = $tile_size - 1;

		$center = $node->getCenter();

		$zoom = 16;
		$world_pow = $zoom + $tile_pow;
		$world_size = 1 << $world_pow;
		$world_x = $center->x * $world_size;
		$world_y = $center->y * $world_size;
		$world_tile_corner_x = $world_x - $tile_size / 2;
		$world_tile_corner_y = $world_y - $tile_size / 2;
		$fetch_extra_tile_x = $world_tile_corner_x & $tile_mask;
		$fetch_extra_tile_y = $world_tile_corner_y & $tile_mask;
		$tile_x = $world_tile_corner_x >> $tile_pow;
		$tile_y = $world_tile_corner_y >> $tile_pow;

		// TODO skip tiles outsize the world
		$tile_image_00 = $this->fetchTileImage($zoom, $tile_x, $tile_y);
		if ($fetch_extra_tile_x) {
			$tile_image_10 = $this->fetchTileImage($zoom, $tile_x + 1, $tile_y);
		}
		if ($fetch_extra_tile_y) {
			$tile_image_01 = $this->fetchTileImage($zoom, $tile_x, $tile_y + 1);
		}
		if ($fetch_extra_tile_x && $fetch_extra_tile_y) {
			$tile_image_11 = $this->fetchTileImage($zoom, $tile_x + 1, $tile_y + 1);
		}

		$image = imagecreatetruecolor($tile_size, $tile_size);
		$x0 = $world_tile_corner_x & $tile_mask;
		$x1 = $tile_size - $x0;
		$y0 = $world_tile_corner_y & $tile_mask;
		$y1 = $tile_size - $y0;
		if ($tile_image_00) {
			imagecopy($image, $tile_image_00, 0, 0, $x0, $y0, $x1, $y1);
		}
		if ($tile_image_10) {
			imagecopy($image, $tile_image_10, $x1, 0, 0, $y0, $x0, $y1);
		}
		if ($tile_image_01) {
			imagecopy($image, $tile_image_01, 0, $y1, $x0, 0, $x1, $y0);
		}
		if ($tile_image_11) {
			imagecopy($image, $tile_image_11, $x1, $y1, 0, 0, $x0, $y0);
		}

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

	private function fetchTileImage(int $z, int $x, int $y): ?\GdImage {
		$url = $this->osm_tile_url . "$z/$x/$y.png";
		$data = $this->client->fetch($url);
		if ($data === null) return null;
		return imagecreatefromstring($data);
	}
}
