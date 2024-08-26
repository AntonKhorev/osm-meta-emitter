<?php namespace OsmMetaEmitter\OgImage;

interface HttpClient {
	function fetch(string $url): ?string;
}
