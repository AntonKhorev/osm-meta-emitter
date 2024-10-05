<?php namespace OsmMetaEmitter\Log;

abstract class Writer {
	function log(string $message, $timestamp = new \DateTimeImmutable): void {
		$formatted_timestamp = $timestamp->format(\DateTimeImmutable::RFC3339_EXTENDED);
		$this->logRaw("[$formatted_timestamp] $message");
	}

	function logHttp(string $title, array $items, ?int $size = null, $timestamp = new \DateTimeImmutable): void {
		$message = "";
		$message .= "$title (\n";
		foreach ($items as $item) {
			$message .= "    $item\n";
		}
		$message .= ")";
		if ($size !== null) {
			$message .= " $size bytes";
		}
		$this->log($message, $timestamp);
	}

	abstract protected function logRaw(string $message): void;
}
