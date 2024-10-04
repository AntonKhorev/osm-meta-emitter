<?php namespace OsmMetaEmitter;

abstract class Logger {
	abstract function log(string $message): void;

	function logHttp(string $title, array $items, ?int $size = null): void {
		$message = "";
		$message .= "$title (\n";
		foreach ($items as $item) {
			$message .= "    $item\n";
		}
		$message .= ")";
		if ($size !== null) {
			$message .= " $size bytes";
		}
		$this->log($message);
	}
}
