<?php namespace OsmMetaEmitter;

class SyslogLogger extends Logger {
	function __construct() {
		openlog("osm-meta-emitter", LOG_PERROR, LOG_USER);
	}

	function log(string $message): void {
		syslog(LOG_INFO, $message);
	}
}
