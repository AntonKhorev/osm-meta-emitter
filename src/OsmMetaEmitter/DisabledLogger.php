<?php namespace OsmMetaEmitter;

class DisabledLogger extends Logger {
	function logRaw(string $message): void {}
}
