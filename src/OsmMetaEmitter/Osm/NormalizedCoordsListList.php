<?php namespace OsmMetaEmitter\Osm;

class NormalizedCoordsListList implements \IteratorAggregate {
	private array $array;

	function __construct(NormalizedCoordsList ...$array) {
		$this->array = $array;
	}

	function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->array);
	}

	function getBbox(): NullableNormalizedCoordsBbox {
		$bbox = new NullableNormalizedCoordsBbox();
		foreach ($this as $coordsList) {
			$bbox = $bbox->include($coordsList->getBbox());
		}
		return $bbox;
	}
}
