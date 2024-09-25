<?php namespace OsmMetaEmitter\Osm;

class GeometryCollection extends Geometry implements \IteratorAggregate {
	private array $array;

	function __construct(Geometry ...$array) {
		if (count($array) <= 0) throw new EmptyGeometryCollectionException;
		$this->array = $array;
	}

	function getIterator(): \Traversable {
		return new \ArrayIterator($this->array);
	}

	function getBbox(): NormalizedCoordsBbox {
		$bbox = null;
		foreach ($this as $geometry) {
			if ($bbox) {
				$bbox = $bbox->include($geometry->getBbox());
			} else {
				$bbox = $geometry->getBbox();
			}
		}
		return $bbox;
	}
}
