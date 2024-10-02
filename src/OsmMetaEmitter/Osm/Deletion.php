<?php namespace OsmMetaEmitter\Osm;

class Deletion {
	function __construct(
		public int $version,
		public \DateTimeImmutable $timestamp
	) {}
}
