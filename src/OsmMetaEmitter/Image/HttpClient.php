<?php namespace OsmMetaEmitter\Image;

interface HttpClient {
	function fetch(string $url): ?string;
}
