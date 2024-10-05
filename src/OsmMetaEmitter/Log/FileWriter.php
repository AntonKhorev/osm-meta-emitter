<?php namespace OsmMetaEmitter\Log;

class FileWriter extends Writer {
	function logRaw(string $message): void {
		file_put_contents('log', $message . PHP_EOL , FILE_APPEND | LOCK_EX);
	}
}
