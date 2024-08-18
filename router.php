<?php

// requires php with gd (php, php-gd, php-curl packages)

// use this to serve for development purposes:
// php -S localhost:8000 router.php

$config = [
	// "osm_api_url" => "https://api.openstreetmap.org/",
	"osm_api_url" => "http://127.0.0.1:3000/",
	// "osm_tile_url" => "https://tile.openstreetmap.org/",
	"osm_tile_url" => "http://127.0.0.1:8001/",
	"element_pages" => true,
];

if (php_sapi_name() == 'cli-server') {
	$root = "/";
} else {
	if (preg_match('{(.*/)[^/]*\.php$}', $_SERVER['SCRIPT_NAME'], $matches)) {
		$root = $matches[1];
	} else {
		$root = "/";
	}
}

if (substr($_SERVER['REQUEST_URI'], 0, strlen($root)) == $root) {
	$request = substr($_SERVER['REQUEST_URI'], strlen($root));
}

if (preg_match("{^nodes?/(\d+)/image\.png?$}", $request, $match)) {
	global $config;

	$id = $match[1];
	$node = fetch_element("node", $id);

	$tile_pow = 8;
	$tile_size = 1 << $tile_pow;
	$tile_mask = $tile_size - 1;

	$normalized_x = calculate_normalized_x($node->lon);
	$normalized_y = calculate_normalized_y($node->lat);

	$zoom = 16;
	$world_pow = $zoom + $tile_pow;
	$world_size = 1 << $world_pow;
	$world_x = $normalized_x * $world_size;
	$world_y = $normalized_y * $world_size;
	$world_tile_corner_x = $world_x - $tile_size / 2;
	$world_tile_corner_y = $world_y - $tile_size / 2;
	$fetch_extra_tile_x = $world_tile_corner_x & $tile_mask;
	$fetch_extra_tile_y = $world_tile_corner_y & $tile_mask;
	$tile_x = $world_tile_corner_x >> $tile_pow;
	$tile_y = $world_tile_corner_y >> $tile_pow;
	
	// TODO skip tiles outsize the world
	$tile_image_00 = fetch_tile_image($zoom, $tile_x, $tile_y);
	if ($fetch_extra_tile_x) {
		$tile_image_10 = fetch_tile_image($zoom, $tile_x + 1, $tile_y);
	}
	if ($fetch_extra_tile_y) {
		$tile_image_01 = fetch_tile_image($zoom, $tile_x, $tile_y + 1);
	}
	if ($fetch_extra_tile_x && $fetch_extra_tile_y) {
		$tile_image_11 = fetch_tile_image($zoom, $tile_x + 1, $tile_y + 1);
	}

	$image = imagecreatetruecolor($tile_size, $tile_size);
	$x0 = $world_tile_corner_x & $tile_mask;
	$x1 = $tile_size - $x0;
	$y0 = $world_tile_corner_y & $tile_mask;
	$y1 = $tile_size - $y0;
	if ($tile_image_00) {
		imagecopy($image, $tile_image_00, 0, 0, $x0, $y0, $x1, $y1);
	}
	if ($tile_image_10) {
		imagecopy($image, $tile_image_10, $x1, 0, 0, $y0, $x0, $y1);
	}
	if ($tile_image_01) {
		imagecopy($image, $tile_image_01, 0, $y1, $x0, 0, $x1, $y0);
	}
	if ($tile_image_11) {
		imagecopy($image, $tile_image_11, $x1, $y1, 0, 0, $x0, $y0);
	}

	header("Content-Type: image/png");
	imagepng($image);
} elseif ($config['element_pages'] && preg_match("{^nodes?/(\d+)/?$}", $request, $match)) {
	$id = $match[1];
	header("Content-Type: text/plain");
	echo "requested node #$id\n";
	$node = fetch_element("node", $id);
	print_r($node);
} else {
	header("HTTP/1.1 404 Not Found");
	header("Content-Type: text/plain");
	echo "not found\n";
}

function fetch_element(string $type, int $id): object {
	global $config;

	$url = "$config[osm_api_url]api/0.6/$type/$id.json";
	$response_string = fetch($url);
	$response = json_decode($response_string);
	return $response->elements[0];
}

function fetch_tile_image(int $z, int $x, int $y): GdImage {
	global $config;

	$url = "$config[osm_tile_url]$z/$x/$y.png";
	$data = fetch($url);
	return imagecreatefromstring($data);
}

function fetch(string $url): string {
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT, "osm-og-image curl/" . curl_version()["version"]);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response_string = curl_exec($ch);
	curl_close($ch);
	return $response_string;
}

function calculate_normalized_x(float $lon): float {
	return ($lon + 180) / 360;
}

function calculate_normalized_y(float $lat): float {
	$max_lat=85.0511287798;
	$lat = max($lat, -$max_lat);
	$lat = min($lat, +$max_lat);
	$lat_radians = $lat * M_PI / 180;
	return (1 - log(tan($lat_radians) + 1 / cos($lat_radians)) / M_PI) / 2;
}
