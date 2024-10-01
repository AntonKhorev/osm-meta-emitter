<?php namespace OsmMetaEmitter\Osm;

class Element {
	public const DEFAULT_MAX_ZOOM = 16;
	public const AMENITY_MAX_ZOOM = 18;
	public const AMENITY_MAX_ZOOM_TAG_VALUES = [
		"atm", "bureau_de_change", "bank", // https://github.com/gravitystorm/openstreetmap-carto/blob/23b1cfa7284ac91bb78390fa4cb7f1c2c6350b92/style/amenity-points.mss#L66
		"biergarten", "cafe", "fast_food", "food_court", "ice_cream", "pub", "restaurant" // https://github.com/gravitystorm/openstreetmap-carto/blob/23b1cfa7284ac91bb78390fa4cb7f1c2c6350b92/style/amenity-points.mss#L84
	];
	public bool $visible = true;

	function __construct(
		public Geometry $geometry,
		public int $max_zoom = self::DEFAULT_MAX_ZOOM
	) {}
}
