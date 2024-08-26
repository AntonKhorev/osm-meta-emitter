<?php namespace OsmMetaEmitter\OsmElement;

class Loader {
	function __construct(
		private HttpClient $client,
		private string $osm_api_url
	) {}

	function fetch_node(int $id): Node {
		$data = $this->fetch_element_data("nodes.json?nodes=$id");
		if ($data === null) throw new NotAvailableException("node #$id is not available");
		$node = $this->get_node_or_deletion_from_data($id, $data);
		if ($node instanceof Node) {
			return $node;
		}

		if ($node->version <= 1) throw new NotAvailableException("node #$id is deleted with a version that is too low");
		$previous_version = $node->version - 1;
		$previous_data = $this->fetch_element_data("node/$id/$previous_version.json");
		if ($previous_data === null) throw new NotAvailableException("node #$id is not available when requesting a previous version");
		$previous_node = $this->get_node_or_deletion_from_data($id, $previous_data);
		if ($previous_node instanceof Node) {
			$previous_node->visible = false;
			return $previous_node;
		}
		
		throw new NotAvailableException("node #$id is deleted with a previous version also deleted");
	}

	function fetch_way(int $id): Way {
		$data = $this->fetch_element_data("way/$id/full.json");
		if ($data === null) throw new NotAvailableException("way #$id is not available");
		$way = $this->get_way_or_deletion_from_data($id, $data);
		if ($way instanceof Way) {
			return $way;
		}

		throw new NotAvailableException("way #$id is deleted");
	}

	private function fetch_element_data(string $path): ?object {
		$url = $this->osm_api_url . "api/0.6/$path";
		$response_string = $this->client->fetch($url);
		if ($response_string === null) return null;
		return json_decode($response_string);
	}

	private function get_node_or_deletion_from_data(int $id, object $data): Node | Deletion {
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

	private function get_way_or_deletion_from_data(int $id, object $data): Way | Deletion {
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
}
