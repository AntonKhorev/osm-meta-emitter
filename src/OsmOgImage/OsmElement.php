<?php namespace OsmOgImage;

abstract class OsmElement {
	public bool $visible = true;

	abstract static function fromDecodedJson(int $id, object $data): static | DeletedOsmElement;
	abstract function getCenter(): NormalizedCoords;
}
