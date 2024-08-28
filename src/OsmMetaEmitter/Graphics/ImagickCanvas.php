<?php namespace OsmMetaEmitter\Graphics;

class ImagickCanvas extends Canvas {
	private \Imagick $image;

	function __construct(
		private int $size_x,
		private int $size_y,
		Color $background
	) {
		$this->image = new \Imagick();
		$this->image->newImage($size_x, $size_y, $background->value);
	}

	function pasteImage(string $blob, int $x, int $y): void {
		$src_image = new \Imagick();
		$src_image->readImageBlob($blob);
		$this->image->compositeImage($src_image, \Imagick::COMPOSITE_OVER, $x, $y);
	}

	function drawCrosshair(): void {
		$draw = new \ImagickDraw();
		$draw->setStrokeColor("#80808080");
		$draw->setStrokeWidth(2);
		$draw->line(($this->size_x - 1) / 2, 0, ($this->size_x - 1) / 2, $this->size_y - 1);
		$draw->line(0, ($this->size_y - 1) / 2, $this->size_x - 1, ($this->size_y - 1) / 2);
		$this->image->drawImage($draw);
	}

	function drawPointMarker(float $x, float $y, bool $visible = true): void {
		$draw = new \ImagickDraw();
		if ($visible) {
			$radius = 11;
			$draw->setFillColor("#ff6200");
			$draw->setFillOpacity(0.5);
			$draw->setStrokeColor("#ff6200");
			$draw->setStrokeWidth(3);
			$draw->circle($x - 0.5, $y - 0.5, $x - 0.5 + $radius, $y);
		} else {
			$half_size = 10.5;
			$draw->setStrokeColor("#cc2b48");
			$draw->setStrokeWidth(3);
			$draw->line(
				$x - 0.5 - $half_size, $y - 0.5 - $half_size,
				$x - 0.5 + $half_size, $y - 0.5 + $half_size
			);
			$draw->line(
				$x - 0.5 - $half_size, $y - 0.5 + $half_size,
				$x - 0.5 + $half_size, $y - 0.5 - $half_size
			);
		}
		$this->image->drawImage($draw);
	}

	function drawPolyLine(array $points, bool $visible = true): void {
		$draw = new \ImagickDraw();
		$draw->setFillOpacity(0);
		$draw->setStrokeColor($visible ? "#ff6200" : "#cc2b48");
		$draw->setStrokeWidth(3.5);
		$draw->polyline(array_map(
			fn($point) => ["x" => $point[0] - 0.5, "y" => $point[1] - 0.5],
			$points
		));
		$this->image->drawImage($draw);
	}

	function outputImage(): void {
		header("Content-Type: image/png");
		$this->image->setImageFormat("png");
		echo $this->image;
	}
}
