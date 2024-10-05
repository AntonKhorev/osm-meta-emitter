<?php namespace OsmMetaEmitter\Http;

class EtaggedResponse {
	function __construct(
		public ?string $body = null,
		public ?string $etag = null
	) {}
}
