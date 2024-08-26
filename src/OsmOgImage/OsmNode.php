<?php namespace OsmOgImage;

class OsmNode extends OsmElement {
	function __construct(private NormalizedCoords $point) {}

	public static function fromDecodedJson(int $id, object $data): static | DeletedOsmElement {
		foreach ($data->elements as $element_data) {
			if ($element_data->type == "node" && $element_data->id == $id) {
				$node_data = $element_data;
			}
		}
		if ($node_data === null) throw new OsmElementInvalidDataException("no data provided for requested node #$id");
		if (@$node_data->visible === false) return new DeletedOsmElement($node_data->version);
		$point = NormalizedCoords::fromObject($node_data);
		return new static($point);
	}

	function getCenter(): NormalizedCoords {
		return $this->point;
	}
}
