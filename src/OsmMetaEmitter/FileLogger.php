<?php namespace OsmMetaEmitter;

class FileLogger extends Logger {
	function log(string $message): void {
		file_put_contents('log', $message . PHP_EOL , FILE_APPEND | LOCK_EX);
	}
}
