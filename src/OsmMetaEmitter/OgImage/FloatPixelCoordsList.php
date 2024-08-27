<?php namespace OsmMetaEmitter\OgImage;

class FloatPixelCoordsList implements \IteratorAggregate {
	private array $array;

	function __construct(FloatPixelCoords ...$array) {
		$this->array = $array;
	}

	function getIterator(): \Traversable
	{
		return new \ArrayIterator($this->array);
	}
}
