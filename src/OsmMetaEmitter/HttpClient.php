<?php namespace OsmMetaEmitter;

class HttpClient implements OsmElement\HttpClient {
	function __construct(private string $osm_tile_url, private bool $log_requests) {
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

	function fetch(string $url): ?string {
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
