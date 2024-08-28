<?php namespace OsmMetaEmitter;

class HttpClient implements Osm\HttpClient, OgImage\HttpClient {
	function __construct(private bool $log_requests) {
		if ($this->log_requests) {
			openlog("osm-meta-emitter server", LOG_PERROR, LOG_USER);
		}
	}

	function fetch(string $url, int $timeout = 60): ?string {
		if ($this->log_requests) {
			syslog(LOG_INFO, "http request: $url");
		}
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERAGENT, "osm-meta-emitter curl/" . curl_version()["version"]);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
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
