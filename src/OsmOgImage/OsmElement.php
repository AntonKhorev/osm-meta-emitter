<?php namespace OsmOgImage;

abstract class OsmElement {
	abstract function getCenter(): LatLon;
}
