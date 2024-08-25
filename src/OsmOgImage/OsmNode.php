<?php namespace OsmOgImage;

class OsmNode extends OsmElement {
	function __construct(private NormalizedCoords $point) {}

	public static function fromDecodedJson(int $id, object $data): static {
		foreach ($data->elements as $element_data) {
			if ($element_data->type == "node" && $element_data->id == $id) {
				$node_data = $element_data;
			}
		}
		// TODO throw if not found
		$point = NormalizedCoords::fromObject($node_data);
		return new static($point);
	}

	function getCenter(): NormalizedCoords {
		return $this->point;
	}
}
