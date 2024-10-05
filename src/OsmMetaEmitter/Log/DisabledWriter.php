<?php namespace OsmMetaEmitter\Log;

class DisabledWriter extends Writer {
	function logRaw(string $message): void {}
}
