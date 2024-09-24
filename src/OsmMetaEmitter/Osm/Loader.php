<?php namespace OsmMetaEmitter\Osm;

class Loader {
	function __construct(
		private HttpClient $client,
		private string $osm_api_url
	) {}

	function fetchNode(int $id): Node {
		$data = $this->fetchElementData("nodes.json?nodes=$id");
		if ($data === null) throw new NotAvailableException("node #$id is not available");
		$node = $this->getNodeOrDeletionFromData($id, $data);
		if ($node instanceof Node) {
			return $node;
		}

		if ($node->version <= 1) throw new NotAvailableException("node #$id is deleted with a version that is too low");
		$previous_version = $node->version - 1;
		$previous_data = $this->fetchElementData("node/$id/$previous_version.json");
		if ($previous_data === null) throw new NotAvailableException("node #$id is not available when requesting a previous version");
		$previous_node = $this->getNodeOrDeletionFromData($id, $previous_data);
		if ($previous_node instanceof Node) {
			$previous_node->visible = false;
			return $previous_node;
		}
		
		throw new NotAvailableException("node #$id is deleted with a previous version also deleted");
	}

	function fetchWay(int $id): Way {
		$data = $this->fetchElementData("way/$id/full.json");
		if ($data === null) throw new NotAvailableException("way #$id is not available");
		$way = $this->getWayOrDeletionFromData($id, $data);
		if ($way instanceof Way) {
			return $way;
		}

		throw new NotAvailableException("way #$id is deleted");
	}

	function fetchRelation(int $id): Relation {
		$data = $this->fetchElementData("relation/$id/full.json");
		if ($data === null) throw new NotAvailableException("relation #$id is not available");
		$relation = $this->getRelationOrDeletionFromData($id, $data);
		if ($relation instanceof Relation) {
			return $relation;
		}

		throw new NotAvailableException("relation #$id is deleted");
	}

	private function fetchElementData(string $path): ?object {
		$url = $this->osm_api_url . "api/0.6/$path";
		$response_string = $this->client->fetch($url);
		if ($response_string === null) return null;
		return json_decode($response_string);
	}

	private function getNodeOrDeletionFromData(int $id, object $data): Node | Deletion {
		foreach ($data->elements as $element_data) {
			if ($element_data->type == "node" && $element_data->id == $id) {
				$node_data = $element_data;
			}
		}
		if ($node_data === null) throw new InvalidDataException("no data provided for requested node #$id");
		if (@$node_data->visible === false) return new Deletion($node_data->version);
		$point = NormalizedCoords::fromObject($node_data);
		return new Node($point);
	}

	private function getWayOrDeletionFromData(int $id, object $data): Way | Deletion {
		$node_points = [];
		foreach ($data->elements as $element_data) {
			if ($element_data->type == "node") {
				$node_points[$element_data->id] = NormalizedCoords::fromObject($element_data);
			} elseif ($element_data->type == "way" && $element_data->id == $id) {
				$way_data = $element_data;
			}
		}
		if ($way_data === null) throw new InvalidDataException("no data provided for requested way #$id");
		if (@$way_data->visible === false) return new Deletion($way_data->version);
		$way_points = array_map(fn($node_id) => $node_points[$node_id], $way_data->nodes);
		return new Way(new NormalizedCoordsList(...$way_points));
	}

	private function getRelationOrDeletionFromData(int $id, object $data): Relation | Deletion {
		$nodes_data = [];
		$ways_data = [];
		$relations_data = [];
		foreach ($data->elements as $element_data) {
			if ($element_data->type == "node") {
				$nodes_data[$element_data->id] = $element_data;
			} elseif ($element_data->type == "way") {
				$ways_data[$element_data->id] = $element_data;
			} elseif ($element_data->type == "relation") {
				$relations_data[$element_data->id] = $element_data;
				if ($element_data->id == $id && @$element_data->visible === false) return new Deletion($element_data->version);
			}
		}

		$selected_nodes_data = [];
		$selected_ways_data = [];
		$visited_relations = [];
		$select_elements_data = function(string $type, int $id, int $depth) use (
			&$select_elements_data,
			&$nodes_data, &$ways_data, &$relations_data,
			&$selected_nodes_data, &$selected_ways_data, &$visited_relations,
		) {
			$depth_limit = 10;
			if ($depth >= $depth_limit) return;
			if ($type == "node" && @$nodes_data[$id]) {
				$selected_nodes_data[$id] = $nodes_data[$id];
			} elseif ($type == "way" && @$ways_data[$id]) {
				$selected_ways_data[$id] = $ways_data[$id];
			} elseif ($type == "relation" && @$relations_data[$id] && @!$visited_relations[$id]) {
				$visited_relations[$id] = true;
				foreach ($relations_data[$id]->members as $member_data) {
					$select_elements_data($member_data->type, $member_data->ref, $depth + 1);
				}
			}
		};
		$select_elements_data("relation", $id, 0);

		$points = new NormalizedCoordsList(
			...array_map(fn($node_data) => NormalizedCoords::fromObject($node_data), $selected_nodes_data)
		);
		$lines = new NormalizedCoordsListList(
			...array_map(fn($way_data) => new NormalizedCoordsList(
				...array_map(
					fn($node_id) => NormalizedCoords::fromObject($nodes_data[$node_id]),
					$way_data->nodes
				)
			), $selected_ways_data)
		);
		// TODO check if nonempty
		return new Relation($points, $lines);
	}
}
