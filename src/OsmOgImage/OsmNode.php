<?php namespace OsmOgImage;

class OsmNode extends OsmElement {
	function __construct(private LatLon $point) {}

	public static function fromDecodedJson(int $id, object $data): static {
		foreach ($data->elements as $element_data) {
			if ($element_data->type == "node" && $element_data->id == $id) {
				$node_data = $element_data;
			}
		}
		// TODO throw if not found
		$point = new LatLon($node_data->lat, $node_data->lon);
		return new static($point);
	}

	function getCenter(): LatLon {
		return $this->point;
	}
}
