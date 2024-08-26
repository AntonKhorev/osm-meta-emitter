<?php namespace OsmOgImage\OsmElement;

interface HttpClient {
	function fetch(string $url): ?string;
}
