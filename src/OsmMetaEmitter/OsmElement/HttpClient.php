<?php namespace OsmMetaEmitter\OsmElement;

interface HttpClient {
	function fetch(string $url): ?string;
}
