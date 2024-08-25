<?php namespace OsmOgImage;

abstract class OsmElement {
	abstract function getCenter(): LatLon;
	abstract static function fromDecodedJson(int $id, object $data): static;
}
