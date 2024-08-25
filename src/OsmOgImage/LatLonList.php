<?php namespace OsmOgImage;

class LatLonList implements \IteratorAggregate {
	private array $array;

	function __construct(LatLon ...$array) {
		$this->array = $array;
	}

	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->array);
	}
}
