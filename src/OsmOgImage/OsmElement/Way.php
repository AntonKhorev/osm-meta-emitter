<?php namespace OsmOgImage\OsmElement;

class Way extends Element {
	function __construct(private NormalizedCoordsList $points) {} // TODO throw if empty list

	function getCenter(): NormalizedCoords {
		$min_x = 1;
		$min_y = 1;
		$max_x = 0;
		$max_y = 0;
		foreach ($this->points as $point) {
			$min_x = min($min_x, $point->x);
			$min_y = min($min_y, $point->y);
			$max_x = max($max_x, $point->x);
			$max_y = max($max_y, $point->y);
		}
		return new NormalizedCoords(($min_x + $max_x) / 2, ($min_y + $max_y) / 2);
	}
}
