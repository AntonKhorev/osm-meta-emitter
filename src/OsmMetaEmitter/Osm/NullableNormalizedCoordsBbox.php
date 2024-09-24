<?php namespace OsmMetaEmitter\Osm;

class NullableNormalizedCoordsBbox {
	function __construct(protected NormalizedCoordsBbox | null $bbox = null) {}

	function reify(): NormalizedCoordsBbox {
		if ($this->bbox == null) throw new \Exception("unexpected null bbox");
		return $this->bbox;
	}

	function include(NormalizedCoords | NullableNormalizedCoordsBbox $subject): NullableNormalizedCoordsBbox {
		if ($subject instanceof NormalizedCoords) {
			return $this->includeCoords($subject);
		} else {
			return $this->includeNullableBbox($subject);
		}
	}

	private function includeCoords(NormalizedCoords $coords): NullableNormalizedCoordsBbox {
		if ($this->bbox == null) {
			return new NullableNormalizedCoordsBbox(
				new NormalizedCoordsBbox($coords, $coords)
			);
		} else {
			return new NullableNormalizedCoordsBbox(
				new NormalizedCoordsBbox(
					new NormalizedCoords(
						min($this->bbox->min->x, $coords->x),
						min($this->bbox->min->y, $coords->y)
					),
					new NormalizedCoords(
						max($this->bbox->max->x, $coords->x),
						max($this->bbox->max->y, $coords->y)
					)
				)
			);
		}
	}

	private function includeNullableBbox(NullableNormalizedCoordsBbox $that): NullableNormalizedCoordsBbox {
		if ($this->bbox == null) {
			return $that;
		} elseif ($that->bbox == null) {
			return $this;
		} else {
			return new NullableNormalizedCoordsBbox(
				new NormalizedCoordsBbox(
					new NormalizedCoords(
						min($this->bbox->min->x, $that->bbox->min->x),
						min($this->bbox->min->y, $that->bbox->min->y)
					),
					new NormalizedCoords(
						max($this->bbox->max->x, $that->bbox->max->x),
						max($this->bbox->max->y, $that->bbox->max->y)
					)
				)
			);
		}
	}
}
