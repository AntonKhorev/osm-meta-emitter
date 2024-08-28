<?php namespace OsmMetaEmitter\Graphics;

class ImagickCanvasFactory extends CanvasFactory {
	function makeCanvas(int $size_x, int $size_y, Color $background): Canvas {
		return new ImagickCanvas($size_x, $size_y, $background);
	}
}
