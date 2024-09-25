<?php namespace OsmMetaEmitter\Osm;

class NormalizedCoordsBbox {
	function __construct(
		public NormalizedCoords $min,
		public NormalizedCoords $max
	) {}

	function getCenter(): NormalizedCoords {
		return new NormalizedCoords(
			($this->min->x + $this->max->x) / 2,
			($this->min->y + $this->max->y) / 2
		);
	}

	function include(NormalizedCoords | NormalizedCoordsBbox $subject): NormalizedCoordsBbox {
		if ($subject instanceof NormalizedCoords) {
			return $this->includeCoords($subject);
		} else {
			return $this->includeBbox($subject);
		}
	}

	private function includeCoords(NormalizedCoords $coords): NormalizedCoordsBbox {
		return new NormalizedCoordsBbox(
			new NormalizedCoords(
				min($this->min->x, $coords->x),
				min($this->min->y, $coords->y)
			),
			new NormalizedCoords(
				max($this->max->x, $coords->x),
				max($this->max->y, $coords->y)
			)
		);
	}

	private function includeBbox(NormalizedCoordsBbox $that): NormalizedCoordsBbox {
		return new NormalizedCoordsBbox(
			new NormalizedCoords(
				min($this->min->x, $that->min->x),
				min($this->min->y, $that->min->y)
			),
			new NormalizedCoords(
				max($this->max->x, $that->max->x),
				max($this->max->y, $that->max->y)
			)
		);
	}
}
