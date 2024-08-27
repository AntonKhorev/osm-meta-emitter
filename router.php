<?php

// requires php with gd (php, php-gd, php-curl packages)

// use this to serve for development purposes:
// php -S localhost:8000 router.php

spl_autoload_register(function ($class_name) {
	$class_path = str_replace("\\", "/", $class_name);
	$filename = __DIR__ . "/src/" . $class_path . ".php";
	if (file_exists($filename)) require $filename;
});

$settings = OsmMetaEmitter\Settings::read();

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

$client = new OsmMetaEmitter\HttpClient($settings["osm_tile_url"], $settings["log_http_requests"]);
$page = new OsmMetaEmitter\WebPage(
	(@$_SERVER['HTTPS'] ? "https" : "http") . "://$_SERVER[HTTP_HOST]${root}",
	$settings["osm_web_url"], $settings["site_name"], $settings["site_description"]
);
$image_size = new OsmMetaEmitter\OgImage\IntPixelSize($settings["image_size_x"], $settings["image_size_y"]);

if (preg_match("{^nodes?/(\d+)/image\.png?$}", $request, $match)) {
	$id = $match[1];
	$loader = new OsmMetaEmitter\OsmElement\Loader($client, $settings["osm_api_url"]);
	$image = new OsmMetaEmitter\OgImage\Writer($client, $settings["osm_tile_url"], $image_size);
	try {
		$node = $loader->fetchNode($id);
		$image->respondWithNodeImage($node, $settings["image_crosshair"]);
	} catch (OsmMetaEmitter\OsmElement\Exception) {
		respond_with_dummy_image();
	}
} elseif (preg_match("{^ways?/(\d+)/image\.png?$}", $request, $match)) {
	$id = $match[1];
	$loader = new OsmMetaEmitter\OsmElement\Loader($client, $settings["osm_api_url"]);
	$image = new OsmMetaEmitter\OgImage\Writer($client, $settings["osm_tile_url"], $image_size);
	try {
		$way = $loader->fetchWay($id);
		$image->respondWithWayImage($way, $settings["image_crosshair"]);
	} catch (OsmMetaEmitter\OsmElement\Exception) {
		respond_with_dummy_image();
	}
} elseif ($settings["element_pages"] && preg_match("{^nodes?/(\d+)/?$}", $request, $match)) {
	$id = $match[1];
	$page->respondWithNodePage($id);
} elseif ($settings["element_pages"] && preg_match("{^ways?/(\d+)/?$}", $request, $match)) {
	$id = $match[1];
	$page->respondWithWayPage($id);
} else {
	header("HTTP/1.1 404 Not Found");
	header("Content-Type: text/plain");
	echo "not found\n";
}

function respond_with_dummy_image(): void {
	global $settings;

	header("Content-Type: image/png");
	readfile($settings["site_logo"]);
}
