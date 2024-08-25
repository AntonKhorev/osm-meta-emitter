<?php namespace OsmOgImage;

class NormalizedCoordsList implements \IteratorAggregate {
	private array $array;

	function __construct(NormalizedCoords ...$array) {
		$this->array = $array;
	}

	public function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->array);
	}
}
