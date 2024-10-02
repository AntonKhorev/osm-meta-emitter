<?php namespace OsmMetaEmitter\Osm;

const DEFAULT_MAX_ZOOM = 16;
const PLACE_MAX_ZOOM = 13; // https://github.com/gravitystorm/openstreetmap-carto/blob/23b1cfa7284ac91bb78390fa4cb7f1c2c6350b92/style/placenames.mss#L155
const AMENITY_MAX_ZOOMS = [
	// https://github.com/gravitystorm/openstreetmap-carto/blob/23b1cfa7284ac91bb78390fa4cb7f1c2c6350b92/style/amenity-points.mss#L66
	"atm" => 18, "bureau_de_change" => 18, "bank" => 18,
	// https://github.com/gravitystorm/openstreetmap-carto/blob/23b1cfa7284ac91bb78390fa4cb7f1c2c6350b92/style/amenity-points.mss#L84
	"biergarten" => 18, "cafe" => 18, "fast_food" => 18, "food_court" => 18, "ice_cream" => 18, "pub" => 18, "restaurant" => 18,
	// https://github.com/gravitystorm/openstreetmap-carto/blob/23b1cfa7284ac91bb78390fa4cb7f1c2c6350b92/style/amenity-points.mss#L1581
	"bench" => 19, "waste_basket" => 19, "waste_disposal" => 19,
];

class CartoMaxZoomAlgorithm extends MaxZoomAlgorithm {
	function getMaxZoomFromTags(array $tags): int {
		if (@$tags["place"]) return PLACE_MAX_ZOOM;
		if (@$tags["amenity"] && @AMENITY_MAX_ZOOMS[$tags["amenity"]]) return AMENITY_MAX_ZOOMS[$tags["amenity"]];
		return DEFAULT_MAX_ZOOM;
	}
}
