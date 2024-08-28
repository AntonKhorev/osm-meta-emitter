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

	function getBbox(): NormalizedCoordsBbox {
		$min_x = 1;
		$min_y = 1;
		$max_x = 0;
		$max_y = 0;
		foreach ($this as $coords) {
			$min_x = min($min_x, $coords->x);
			$min_y = min($min_y, $coords->y);
			$max_x = max($max_x, $coords->x);
			$max_y = max($max_y, $coords->y);
		}
		return new NormalizedCoordsBbox(
			new NormalizedCoords($min_x, $min_y),
			new NormalizedCoords($max_x, $max_y)
		);
	}
}
