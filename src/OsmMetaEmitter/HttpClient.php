<?php namespace OsmMetaEmitter;

class HttpClient implements Osm\HttpClient, Image\HttpClient {
	function __construct(
		private Logger $logger
	) {}

	function fetch(string $url, int $timeout = 60): ?string {
		$ch = curl_init();
		$this->setCommonCurlOptions($ch, $url, $timeout);

		$response_headers = [];
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($_, $header) use (&$response_headers) {
			if (strlen(rtrim($header))) $response_headers[] = rtrim($header);
			return strlen($header);
		});

		$response_string = curl_exec($ch);
		curl_close($ch);
		$this->logRequest($ch, $url, $response_headers);

		$response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		if ($response_code != 200) return null;
		return $response_string;
	}

	function fetchWithEtag(string $url, int $timeout = 60, ?string $etag = null): ?EtaggedResponse {
		$ch = curl_init();
		$this->setCommonCurlOptions($ch, $url, $timeout);
		if ($etag !== null) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, ["If-None-Match: \"$etag\""]);
		}

		$response_etag = null;
		$response_headers = [];
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($_, $header) use (&$response_etag, &$response_headers) {
			if (preg_match('/^\s*etag:\s*"([^"]*)"/i', $header, $matches)) {
				$response_etag = $matches[1];
			}
			if (strlen(rtrim($header))) $response_headers[] = rtrim($header);
			return strlen($header);
		});

		$response_string = curl_exec($ch);
		curl_close($ch);
		$this->logRequest($ch, $url, $response_headers);

		$response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		if ($response_code == 304) {
			return new EtaggedResponse(null, $response_etag ?? $etag);
		} elseif ($response_code == 200) {
			return new EtaggedResponse($response_string, $response_etag);
		}
		return null;
	}

	private function setCommonCurlOptions(\CurlHandle $ch, string $url, int $timeout): void {
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "osm-meta-emitter curl/" . curl_version()["version"]);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	}

	private function logRequest(\CurlHandle $ch, string $url, array $response_headers): void {
		$request_headers = [];
		foreach (preg_split("/\\R/", curl_getinfo($ch, CURLINFO_HEADER_OUT)) as $header) {
			if (strlen($header) > 0) $request_headers[] = $header;
		}
		$this->logger->logHttp("client --- self --> $url", $request_headers, curl_getinfo($ch, CURLINFO_SIZE_UPLOAD));
		$this->logger->logHttp("client --- self <-- $url", $response_headers, curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD));
	}
}
