<?php namespace OsmMetaEmitter\Graphics;

class GdCanvas extends Canvas {
	private \GdImage $image;

	function __construct(
		private int $size_x,
		private int $size_y,
		Color $background
	) {
		$this->image = imagecreatetruecolor($size_x, $size_y);
		$background_color = imagecolorallocate($this->image, ...$this->getRgb($background, false));
		imagefilledrectangle($this->image, 0, 0, $size_x, $size_y, $background_color);
	}

	function pasteImage(
		string $src_blob,
		int $dst_x, int $dst_y,
		int $src_x, int $src_y,
		int $src_size_x, int $src_size_y
	): void {
		$src_image = imagecreatefromstring($src_blob);
		imagecopy(
			$this->image, $src_image,
			$dst_x, $dst_y,
			$src_x, $src_y,
			$src_size_x, $src_size_y
		);
	}

	function drawCrosshair(): void {
		$crosshair_color = imagecolorallocatealpha($this->image, 128, 128, 128, 64);
		imageline($this->image, $this->size_x / 2, 0, $this->size_x / 2, $this->size_y - 1, $crosshair_color);
		imageline($this->image, $this->size_x / 2 + 1, 0, $this->size_x / 2 + 1, $this->size_y - 1, $crosshair_color);
		imageline($this->image, 0, $this->size_y / 2, $this->size_x - 1, $this->size_y / 2, $crosshair_color);
		imageline($this->image, 0, $this->size_y / 2 + 1, $this->size_x - 1, $this->size_y / 2 + 1, $crosshair_color);
	}

	function drawPointMarker(float $x, float $y, bool $visible = true): void {
		if ($visible) {
			$marker_image = imagecreatefrompng("assets/node_marker.png");
		} else {
			$marker_image = imagecreatefrompng("assets/deleted_node_marker.png");
		}
		imagecopy(
			$this->image, $marker_image,
			$x - imagesx($marker_image) / 2 + 1,
			$y - imagesy($marker_image) / 2 + 1,
			0, 0,
			imagesx($marker_image), imagesy($marker_image)
		);
	}

	function drawPolyLine(array $points, bool $visible = true): void {
		$gd_points = [];
		foreach ($points as $point) {
			$gd_points[] = floor($point[0]);
			$gd_points[] = floor($point[1]);
		}
		if ($visible) {
			$line_color = imagecolorallocate($this->image, 255, 98, 0);
		} else {
			$line_color = imagecolorallocate($this->image, 204, 43, 72);
		}
		imagesetthickness($this->image, 4);
		imageopenpolygon($this->image, $gd_points, $line_color);
	}

	function outputImage(): void {
		header("Content-Type: image/png");
		imagepng($this->image);
	}

	private function getRgb(Color $color, bool $active = true): array {
		try {
			return $color->getRgb();
		} catch (\Exception) {
			if ($active) {
				return [255, 255, 255];
			} else {
				return [0, 0, 0];
			}
		}
	}
}
