<?php namespace OsmMetaEmitter;

class EtaggedResponse {
	function __construct(
		public ?string $body = null,
		public ?string $etag = null
	) {}
}
