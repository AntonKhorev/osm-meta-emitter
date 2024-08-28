<?php namespace OsmMetaEmitter\Graphics;

abstract class Canvas {
	abstract function pasteImage(string $blob, int $x, int $y): void;
	abstract function drawCrosshair(): void;
	abstract function drawPointMarker(float $x, float $y, bool $visible = true): void;
	abstract function drawPolyLine(array $points, bool $visible = true): void;

	abstract function outputImage(): void;
}
