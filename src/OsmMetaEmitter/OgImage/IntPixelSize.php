<?php namespace OsmMetaEmitter\OgImage;

class IntPixelSize {
	function __construct(
		public int $x,
		public int $y
	) {}

	function withoutMargins(int $margins): IntPixelSize {
		return new IntPixelSize(
			$this->x - 2 * $margins,
			$this->y - 2 * $margins
		);
	}

	function fitsInto(IntPixelSize $size_to_fit_into): bool {
		return $this->x <= $size_to_fit_into->x && $this->y <= $size_to_fit_into->y;
	}
}
