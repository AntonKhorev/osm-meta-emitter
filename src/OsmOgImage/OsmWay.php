<?php namespace OsmOgImage;

class OsmWay extends OsmElement {
	function __construct(private LatLonList $points) {}

	public static function fromDecodedJson(int $id, object $data): static {
		$node_points = [];
		foreach ($data->elements as $element_data) {
			if ($element_data->type == "node") {
				$node_points[$element_data->id] = new LatLon($element_data->lat, $element_data->lon);
			} elseif ($element_data->type == "way" && $element_data->id == $id) {
				$way_data = $element_data;
			}
		}
		// TODO throw if not found
		$way_points = array_map(fn($node_id) => $node_points[$node_id], $way_data->nodes);
		return new static(new LatLonList(...$way_points));
	}

	function getCenter(): LatLon {
		// TODO bbox etc
		foreach ($this->points as $point) {
			return $point;
		}
	}
}
