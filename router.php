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

	$z = 16;
	$x = floor(calculate_normalized_x($node->lon) * (1 << $z));
	$y = floor(calculate_normalized_y($node->lat) * (1 << $z));
	$image_url = "$config[osm_tile_url]$z/$x/$y.png";
	$image_data = fetch($image_url);

	// $image = imagecreatetruecolor(256, 256);
	$image = imagecreatefromstring($image_data);
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
