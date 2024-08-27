<?php namespace OsmMetaEmitter\Graphics;

class GdCanvasFactory extends CanvasFactory {
	function makeCanvas(int $size_x, int $size_y, Color $background): Canvas {
		return new GdCanvas($size_x, $size_y, $background);
	}
}
