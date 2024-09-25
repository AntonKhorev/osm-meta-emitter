<?php namespace OsmMetaEmitter\Osm;

class LineString extends Geometry {
	public array $coords_array;

	function __construct(NormalizedCoords ...$coords_array) {
		if (count($coords_array) <= 0) throw new EmptyLineStringException;
		$this->coords_array = $coords_array;
	}

	function getBbox(): NormalizedCoordsBbox {
		$bbox = null;
		foreach ($this->coords_array as $coords) {
			if ($bbox) {
				$bbox = $bbox->include($coords);
			} else {
				$bbox = new NormalizedCoordsBbox($coords, $coords);
			}
		}
		return $bbox;
	}
}
