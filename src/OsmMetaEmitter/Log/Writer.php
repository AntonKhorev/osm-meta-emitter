<?php namespace OsmMetaEmitter\Log;

abstract class Writer {
	function log(string $message, $timestamp = new \DateTimeImmutable): void {
		$formatted_timestamp = $timestamp->format(\DateTimeImmutable::RFC3339_EXTENDED);
		$this->logRaw("[$formatted_timestamp] $message");
	}

	abstract protected function logRaw(string $message): void;
}
