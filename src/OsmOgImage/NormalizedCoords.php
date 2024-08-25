<?php namespace OsmOgImage;

// coords in range [0..1]

class NormalizedCoords {
	function __construct(public float $x, public float $y) {}

	static function fromObject(object $data): static {
		return static::fromLatLon($data->lat, $data->lon);
	}

	static function fromLatLon(float $lat, float $lon): static {
		return new static(
			static::calculate_normalized_x($lon),
			static::calculate_normalized_y($lat)
		);
	}

	private static function calculate_normalized_x(float $lon): float {
		return ($lon + 180) / 360;
	}
	
	private static function calculate_normalized_y(float $lat): float {
		$max_lat=85.0511287798;
		$lat = max($lat, -$max_lat);
		$lat = min($lat, +$max_lat);
		$lat_radians = $lat * M_PI / 180;
		return (1 - log(tan($lat_radians) + 1 / cos($lat_radians)) / M_PI) / 2;
	}
}
