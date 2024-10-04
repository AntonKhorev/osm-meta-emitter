<?php namespace OsmMetaEmitter;

class DisabledLogger extends Logger {
	function log(string $message): void {}
}
