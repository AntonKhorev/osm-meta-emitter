<?php

// requires php with gd (php, php-gd, php-curl packages)

// use this to serve for development purposes:
// php -S localhost:8000 router.php

$settings = [];
read_settings_file("settings.ini");
read_settings_file("settings.local.ini");

if (php_sapi_name() == "cli-server") {
	$root = "/";
} else {
	if (preg_match("{(.*/)[^/]*\.php$}", $_SERVER["SCRIPT_NAME"], $matches)) {
		$root = $matches[1];
	} else {
		$root = "/";
	}
}

if (substr($_SERVER['REQUEST_URI'], 0, strlen($root)) == $root) {
	$request = substr($_SERVER['REQUEST_URI'], strlen($root));
}

spl_autoload_register(function ($class_name) {
	$class_path = str_replace("\\", "/", $class_name);
	$filename = __DIR__ . "/src/" . $class_path . ".php";
	if (file_exists($filename)) require $filename;
});

if (preg_match("{^nodes?/(\d+)/image\.png?$}", $request, $match)) {
	$id = $match[1];
	$client = new OsmOgImage\HttpClient($settings["osm_api_url"], $settings["osm_tile_url"]);
	$node = $client->fetch_node($id);
	if ($node === null) {
		respond_with_dummy_image();
	} else {
		respond_with_node_image($client, $node);
	}
} elseif (preg_match("{^ways?/(\d+)/image\.png?$}", $request, $match)) {
	$id = $match[1];
	$client = new OsmOgImage\HttpClient($settings["osm_api_url"], $settings["osm_tile_url"]);
	$way = $client->fetch_way($id);
	if ($way === null) {
		respond_with_dummy_image();
	} else {
		// TODO
		$fake_node = new OsmOgImage\OsmNode($way->getCenter());
		respond_with_node_image($client, $fake_node);
	}
} elseif ($settings["element_pages"] && preg_match("{^nodes?/(\d+)/?$}", $request, $match)) {
	$id = $match[1];
	respond_with_node_page($id);
} else {
	header("HTTP/1.1 404 Not Found");
	header("Content-Type: text/plain");
	echo "not found\n";
}

function read_settings_file(string $filename): void {
	global $settings;

	$new_settings = @parse_ini_file($filename);
	if ($new_settings) {
		$settings = array_merge($settings, $new_settings);
	}
}

function respond_with_dummy_image(): void {
	global $settings;

	header("Content-Type: image/png");
	readfile($settings["dummy_image_file"]);
}

function respond_with_node_image(OsmOgImage\HttpClient $client, OsmOgImage\OsmNode $node): void {
	global $settings;

	$tile_pow = 8;
	$tile_size = 1 << $tile_pow;
	$tile_mask = $tile_size - 1;

	$center = $node->getCenter();
	$normalized_x = calculate_normalized_x($center->lon);
	$normalized_y = calculate_normalized_y($center->lat);

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
	$tile_image_00 = $client->fetch_tile_image($zoom, $tile_x, $tile_y);
	if ($fetch_extra_tile_x) {
		$tile_image_10 = $client->fetch_tile_image($zoom, $tile_x + 1, $tile_y);
	}
	if ($fetch_extra_tile_y) {
		$tile_image_01 = $client->fetch_tile_image($zoom, $tile_x, $tile_y + 1);
	}
	if ($fetch_extra_tile_x && $fetch_extra_tile_y) {
		$tile_image_11 = $client->fetch_tile_image($zoom, $tile_x + 1, $tile_y + 1);
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
	
	if ($settings["image_crosshair"]) {
		$crosshair_color = imagecolorallocatealpha($image, 128, 128, 128, 64);
		imageline($image, $tile_size / 2, 0, $tile_size / 2, $tile_size - 1, $crosshair_color);
		imageline($image, $tile_size / 2 + 1, 0, $tile_size / 2 + 1, $tile_size - 1, $crosshair_color);
		imageline($image, 0, $tile_size / 2, $tile_size - 1, $tile_size / 2, $crosshair_color);
		imageline($image, 0, $tile_size / 2 + 1, $tile_size - 1, $tile_size / 2 + 1, $crosshair_color);
	}

	$marker_image = imagecreatefrompng("node_marker.png");
	imagecopy(
		$image, $marker_image,
		$tile_size / 2 - imagesx($marker_image) / 2 + 1, $tile_size / 2 - imagesy($marker_image) / 2 + 1,
		0, 0,
		imagesx($marker_image), imagesy($marker_image)
	);

	header("Content-Type: image/png");
	imagepng($image);
}

function respond_with_node_page(int $id): void {
	global $settings, $root;

	$title = "Node: $id";
	$osm_url = "$settings[osm_web_url]node/$id";
	$image_url = (@$_SERVER['HTTPS'] ? "https" : "http") . "://$_SERVER[HTTP_HOST]${root}node/$id/image.png";

	echo "<!DOCTYPE html>\n";
	echo "<html lang=en>\n";
	echo "<head>\n";
	echo meta_tag("og:site_name", $settings["og:site_name"]);
	echo meta_tag("og:title", $title);
	echo meta_tag("og:type", "website");
	echo meta_tag("og:url", $osm_url);
	echo meta_tag("og:description", $settings["og:description"]);
	echo meta_tag("og:image", $image_url);
	echo meta_tag("og:image:alt", "Node location");
	echo "</head>\n";
	echo "<body>\n";
	echo "<h1>" . htmlspecialchars($title) . "</h1>\n";
	echo "<p>See on <a href='" . htmlspecialchars($osm_url) . "'>" . htmlspecialchars($settings["og:site_name"]) . "</a></p>\n";
	echo "</body>\n";
	echo "</html>\n";
}

function meta_tag(string $property, string $content): string {
	return "<meta property='" . htmlspecialchars($property) . "' content='" . htmlspecialchars($content) . "'>\n";
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
