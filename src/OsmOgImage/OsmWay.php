<?php namespace OsmOgImage;

class OsmWay extends OsmElement {
	function __construct(private LatLonList $points) {} // TODO throw if empty list

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
		$min_lat = +INF;
		$min_lon = +INF;
		$max_lat = -INF;
		$max_lon = -INF;
		foreach ($this->points as $point) {
			$min_lat = min($min_lat, $point->lat);
			$min_lon = min($min_lon, $point->lon);
			$max_lat = max($max_lat, $point->lat);
			$max_lon = max($max_lon, $point->lon);
		}
		// TODO this is wrong, should center in mercator coords
		return new LatLon(($min_lat + $max_lat) / 2, ($min_lon + $max_lon) / 2);
	}
}
