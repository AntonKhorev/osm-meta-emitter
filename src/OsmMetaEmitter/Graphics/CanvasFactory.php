<?php namespace OsmMetaEmitter\Graphics;

abstract class CanvasFactory {
	abstract function makeCanvas(int $size_x, int $size_y, Color $background): Canvas;
}
