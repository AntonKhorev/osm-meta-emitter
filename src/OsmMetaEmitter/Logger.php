<?php namespace OsmMetaEmitter;

abstract class Logger {
	function log(string $message): void {
		$timestamp = (new \DateTime)->format(\DateTime::RFC3339_EXTENDED);
		$this->logRaw("[$timestamp] $message");
	}

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

	abstract protected function logRaw(string $message): void;
}
