<?php namespace OsmMetaEmitter\Osm;

class ApiLoader extends Loader {
	function __construct(
		private HttpClient $client,
		private string $osm_api_url
	) {}

	function fetchNode(int $id): Element {
		$data = $this->fetchElementData("nodes.json?nodes=$id");
		if ($data === null) throw new NotAvailableException("node #$id is not available");
		$node = $this->getNodeOrDeletionFromData($id, $data, $this->getPrehistoricTimestamp());
		if ($node instanceof Element) {
			return $node;
		}

		if ($node->version <= 1) throw new NotAvailableException("node #$id is deleted with a version that is too low");
		$previous_version = $node->version - 1;
		$previous_data = $this->fetchElementData("node/$id/$previous_version.json");
		if ($previous_data === null) throw new NotAvailableException("node #$id is not available when requesting a previous version");
		$previous_node = $this->getNodeOrDeletionFromData($id, $previous_data, $node->timestamp);
		if ($previous_node instanceof Element) {
			$previous_node->visible = false;
			return $previous_node;
		}
		
		throw new NotAvailableException("node #$id is deleted with a previous version also deleted");
	}

	function fetchWay(int $id): Element {
		$data = $this->fetchElementData("way/$id/full.json");
		if ($data === null) throw new NotAvailableException("way #$id is not available");
		$way = $this->getWayOrDeletionFromData($id, $data, $this->getPrehistoricTimestamp());
		if ($way instanceof Element) {
			return $way;
		}

		throw new NotAvailableException("way #$id is deleted");
	}

	function fetchRelation(int $id): Element {
		$data = $this->fetchElementData("relation/$id/full.json");
		if ($data === null) throw new NotAvailableException("relation #$id is not available");
		$relation = $this->getRelationOrDeletionFromData($id, $data, $this->getPrehistoricTimestamp());
		if ($relation instanceof Element) {
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

	private function getNodeOrDeletionFromData(int $id, object $data, \DateTimeImmutable $timestamp): Element | Deletion {
		foreach ($data->elements as $element_data) {
			if ($element_data->type == "node" && $element_data->id == $id) {
				$node_data = $element_data;
			}
		}
		if ($node_data === null) throw new InvalidDataException("no data provided for requested node #$id");
		$timestamp = max($timestamp, date_create_immutable($node_data->timestamp));
		if (@$node_data->visible === false) return new Deletion($node_data->version, $timestamp);
		$point = new Point(NormalizedCoords::fromObject($node_data));
		return new Element($point, @(array)$node_data->tags, $timestamp);
	}

	private function getWayOrDeletionFromData(int $id, object $data, \DateTimeImmutable $timestamp): Element | Deletion {
		$node_coords = [];
		foreach ($data->elements as $element_data) {
			$timestamp = max($timestamp, date_create_immutable($element_data->timestamp));
			if ($element_data->type == "node") {
				$node_coords[$element_data->id] = NormalizedCoords::fromObject($element_data);
			} elseif ($element_data->type == "way" && $element_data->id == $id) {
				$way_data = $element_data;
			}
		}
		if ($way_data === null) throw new InvalidDataException("no data provided for requested way #$id");
		if (@$way_data->visible === false) return new Deletion($way_data->version, $timestamp);
		$way_coords = array_map(fn($node_id) => $node_coords[$node_id], $way_data->nodes);
		$line = new LineString(...$way_coords);
		return new Element($line, @(array)$way_data->tags, $timestamp);
	}

	private function getRelationOrDeletionFromData(int $id, object $data, \DateTimeImmutable $timestamp): Element | Deletion {
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
			}
		}
		if (!$relations_data[$id]) throw new InvalidDataException("no data provided for requested relation #$id");

		$selected_nodes_data = [];
		$selected_ways_data = [];
		$visited_relations = [];
		$select_elements_data = function(string $type, int $id, int $depth) use (
			&$select_elements_data, &$timestamp,
			&$nodes_data, &$ways_data, &$relations_data,
			&$selected_nodes_data, &$selected_ways_data, &$visited_relations,
		) {
			$depth_limit = 10;
			if ($depth >= $depth_limit) return;
			if ($type == "node" && @$nodes_data[$id]) {
				$timestamp = max($timestamp, date_create_immutable($nodes_data[$id]->timestamp));
				$selected_nodes_data[$id] = $nodes_data[$id];
			} elseif ($type == "way" && @$ways_data[$id]) {
				$timestamp = max($timestamp, date_create_immutable($ways_data[$id]->timestamp));
				$selected_ways_data[$id] = $ways_data[$id];
			} elseif ($type == "relation" && @$relations_data[$id] && @!$visited_relations[$id]) {
				$timestamp = max($timestamp, date_create_immutable($relations_data[$id]->timestamp));
				$visited_relations[$id] = true;
				if (@$relations_data[$id]->members) {
					foreach ($relations_data[$id]->members as $member_data) {
						$select_elements_data($member_data->type, $member_data->ref, $depth + 1);
					}
				}
			}
		};
		$select_elements_data("relation", $id, 0);
		if (@$relations_data[$id]->visible === false) return new Deletion($relations_data[$id]->version, $timestamp);

		$points = array_map(fn($node_data) => new Point(
			NormalizedCoords::fromObject($node_data)
		), $selected_nodes_data);
		$lines = array_map(fn($way_data) => new LineString(
			...array_map(
				fn($node_id) => NormalizedCoords::fromObject($nodes_data[$node_id]),
				$way_data->nodes
			)
		), $selected_ways_data);
		$geometry = new GeometryCollection(...$points, ...$lines);
		return new Element($geometry, @(array)$relations_data[$id]->tags, $timestamp);
	}

	private function getPrehistoricTimestamp(): \DateTimeImmutable {
		return date_create_immutable("2000-01-01Z");
	}
}
