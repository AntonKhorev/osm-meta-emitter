<?php namespace OsmMetaEmitter\Osm;

interface HttpClient {
	function fetch(string $url): ?string;
}
