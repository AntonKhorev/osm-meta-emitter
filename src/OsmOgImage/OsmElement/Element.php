<?php namespace OsmOgImage\OsmElement;

abstract class Element {
	public bool $visible = true;

	abstract function getCenter(): NormalizedCoords;
}
