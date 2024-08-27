<?php namespace OsmMetaEmitter\Graphics;

abstract class Canvas {
	abstract function pasteImage(
		string $image_blob,
		int $dst_x, int $dst_y,
		int $src_x, int $src_y,
		int $src_size_x, int $src_size_y
	): void;

	abstract function drawCrosshair(): void;
	abstract function drawPointMarker(float $x, float $y, bool $visible): void;
	abstract function drawPolyLine(array $points, bool $visible): void;

	abstract function outputImage(): void;
}
