<?php namespace OsmOgImage;

class HttpClient {
	function __construct(private string $osm_api_url, private string $osm_tile_url, private bool $log_requests) {
		if ($this->log_requests) {
			openlog("osm-og-image server", LOG_PERROR, LOG_USER);
		}
	}

	function fetch_tile_image(int $z, int $x, int $y): ?\GdImage {
		$url = $this->osm_tile_url . "$z/$x/$y.png";
		$data = $this->fetch($url);
		if ($data === null) return null;
		return imagecreatefromstring($data);
	}

	function fetch_node(int $id): OsmNode {
		$data = $this->fetch_element_data("nodes.json?nodes=$id");
		if ($data === null) throw new OsmElementNotAvailableException("node #$id is not available");
		$node = OsmNode::fromDecodedJson($id, $data);
		if ($node instanceof OsmNode) {
			return $node;
		}

		if ($node->version <= 1) throw new OsmElementNotAvailableException("node #$id is deleted with a version that is too low");
		$previous_version = $node->version - 1;
		$previous_data = $this->fetch_element_data("node/$id/$previous_version.json");
		if ($previous_data === null) throw new OsmElementNotAvailableException("node #$id is not available when requesting a previous version");
		$previous_node = OsmNode::fromDecodedJson($id, $previous_data);
		if ($previous_node instanceof OsmNode) {
			$previous_node->visible = false;
			return $previous_node;
		}
		
		throw new OsmElementNotAvailableException("node #$id is deleted with a previous version also deleted");
	}

	function fetch_way(int $id): OsmWay {
		$data = $this->fetch_element_data("way/$id/full.json");
		if ($data === null) throw new OsmElementNotAvailableException("way #$id is not available");
		$way = OsmWay::fromDecodedJson($id, $data);
		if ($way instanceof OsmWay) {
			return $way;
		}

		throw new OsmElementNotAvailableException("way #$id is deleted");
	}

	private function fetch_element_data(string $path): ?object {
		$url = $this->osm_api_url . "api/0.6/$path";
		$response_string = $this->fetch($url);
		if ($response_string === null) return null;
		return json_decode($response_string);
	}

	private function fetch(string $url): ?string {
		if ($this->log_requests) {
			syslog(LOG_INFO, "http request: $url");
		}
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "osm-og-image curl/" . curl_version()["version"]);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response_string = curl_exec($ch);
		curl_close($ch);
		$response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		if ($this->log_requests) {
			syslog(LOG_INFO, "http request response code: $response_code");
		}
		if ($response_code != 200) return null;
		return $response_string;
	}
}
