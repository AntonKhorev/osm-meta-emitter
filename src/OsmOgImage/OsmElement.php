<?php namespace OsmOgImage;

abstract class OsmElement {
	abstract static function fromDecodedJson(int $id, object $data): static;
	abstract function getCenter(): NormalizedCoords;
}
