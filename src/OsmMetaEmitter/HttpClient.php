<?php namespace OsmMetaEmitter;

class HttpClient implements Osm\HttpClient, Image\HttpClient {
	function __construct(
		private Logger $logger
	) {}

	function fetch(string $url, int $timeout = 60): ?string {
		$this->logger->log("outgoing http request: $url");
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "osm-meta-emitter curl/" . curl_version()["version"]);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response_string = curl_exec($ch);
		curl_close($ch);
		$response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		$this->logger->log("outgoing http response code: $response_code");
		if ($response_code != 200) return null;
		return $response_string;
	}

	function fetchWithEtag(string $url, int $timeout = 60): EtaggedResponse {
		$this->logger->log("outgoing http request: $url");
		$response_etag = null;
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "osm-meta-emitter curl/" . curl_version()["version"]);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($_, $header) use (&$response_etag) {
			if (preg_match('/^\s*etag:\s*"([^"]*)"/i', $header, $matches)) {
				$response_etag = $matches[1];
			}
			return strlen($header);
		});
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$response_string = curl_exec($ch);
		curl_close($ch);
		$response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		$this->logger->log("outgoing http response code: $response_code");
		if ($response_code != 200) return new EtaggedResponse;
		return new EtaggedResponse($response_string, $response_etag);
	}
}
