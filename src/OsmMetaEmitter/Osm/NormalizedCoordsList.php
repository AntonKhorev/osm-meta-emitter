<?php namespace OsmMetaEmitter\Osm;

class NormalizedCoordsList implements \IteratorAggregate {
	private array $array;

	function __construct(NormalizedCoords ...$array) {
		$this->array = $array;
	}

	function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->array);
	}

	function getBbox(): NullableNormalizedCoordsBbox {
		$bbox = new NullableNormalizedCoordsBbox();
		foreach ($this as $coords) {
			$bbox = $bbox->include($coords);
		}
		return $bbox;
	}
}
