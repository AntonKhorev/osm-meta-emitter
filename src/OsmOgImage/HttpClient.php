<?php namespace OsmOgImage;

class HttpClient {
	function __construct(private string $osm_api_url, private string $osm_tile_url) {}

	function fetch_tile_image(int $z, int $x, int $y): ?\GdImage {
		$url = $this->osm_tile_url . "$z/$x/$y.png";
		$data = $this->fetch($url);
		if ($data === null) return null;
		return imagecreatefromstring($data);
	}

	function fetch_node(int $id): ?OsmNode {
		$data = $this->fetch_element_data("node/$id.json");
		if ($data === null) return null;
		return OsmNode::fromDecodedJson($id, $data);
	}

	function fetch_way(int $id): ?OsmWay {
		$data = $this->fetch_element_data("way/$id/full.json");
		if ($data === null) return null;
		return OsmWay::fromDecodedJson($id, $data);
	}

	private function fetch_element_data(string $path): ?object {
		$url = $this->osm_api_url . "api/0.6/$path";
		$response_string = $this->fetch($url);
		if ($response_string === null) return null;
		return json_decode($response_string);
	}

	private function fetch(string $url): ?string {
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "osm-og-image curl/" . curl_version()["version"]);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response_string = curl_exec($ch);
		curl_close($ch);
		if (curl_getinfo($ch, CURLINFO_RESPONSE_CODE) != 200) return null;
		return $response_string;
	}
}
