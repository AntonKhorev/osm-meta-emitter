<?php namespace OsmOgImage;

class OsmWay extends OsmElement {
	function __construct(private NormalizedCoordsList $points) {} // TODO throw if empty list

	public static function fromDecodedJson(int $id, object $data): static | DeletedOsmElement {
		$node_points = [];
		foreach ($data->elements as $element_data) {
			if ($element_data->type == "node") {
				$node_points[$element_data->id] = NormalizedCoords::fromObject($element_data);
			} elseif ($element_data->type == "way" && $element_data->id == $id) {
				$way_data = $element_data;
			}
		}
		if ($way_data === null) throw new OsmElementInvalidDataException("no data provided for requested way #$id");
		if (@$way_data->visible === false) return new DeletedOsmElement($way_data->version);
		$way_points = array_map(fn($node_id) => $node_points[$node_id], $way_data->nodes);
		return new static(new NormalizedCoordsList(...$way_points));
	}

	function getCenter(): NormalizedCoords {
		$min_x = 1;
		$min_y = 1;
		$max_x = 0;
		$max_y = 0;
		foreach ($this->points as $point) {
			$min_x = min($min_x, $point->x);
			$min_y = min($min_y, $point->y);
			$max_x = max($max_x, $point->x);
			$max_y = max($max_y, $point->y);
		}
		return new NormalizedCoords(($min_x + $max_x) / 2, ($min_y + $max_y) / 2);
	}
}
