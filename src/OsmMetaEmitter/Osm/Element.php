<?php namespace OsmMetaEmitter\Osm;

const DEFAULT_MAX_ZOOM = 16;
const PLACE_MAX_ZOOM = 13; // https://github.com/gravitystorm/openstreetmap-carto/blob/23b1cfa7284ac91bb78390fa4cb7f1c2c6350b92/style/placenames.mss#L155
const AMENITY_MAX_ZOOM = 18;
const AMENITY_MAX_ZOOM_TAG_VALUES = [
	"atm", "bureau_de_change", "bank", // https://github.com/gravitystorm/openstreetmap-carto/blob/23b1cfa7284ac91bb78390fa4cb7f1c2c6350b92/style/amenity-points.mss#L66
	"biergarten", "cafe", "fast_food", "food_court", "ice_cream", "pub", "restaurant" // https://github.com/gravitystorm/openstreetmap-carto/blob/23b1cfa7284ac91bb78390fa4cb7f1c2c6350b92/style/amenity-points.mss#L84
];

class Element {
	public bool $visible = true;
	public int $max_zoom;

	function __construct(
		public Geometry $geometry,
		?object $tags
	) {
		$this->max_zoom = self::getMaxZoomFromTags($tags);
	}

	private static function getMaxZoomFromTags(?object $tags): int {
		if (!$tags) return DEFAULT_MAX_ZOOM;
		if (@$tags->place) return PLACE_MAX_ZOOM;
		if (in_array(@$tags->amenity, AMENITY_MAX_ZOOM_TAG_VALUES)) return AMENITY_MAX_ZOOM;
		return DEFAULT_MAX_ZOOM;
	}
}
