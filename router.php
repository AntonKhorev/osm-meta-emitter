<?php

// requires php with gd or imagick (php, php-gd or php-imagick, php-curl packages)

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

$loader = new OsmMetaEmitter\Osm\ApiLoader($client, $settings["osm_api_url"]);

$image_size = new OsmMetaEmitter\Image\IntPixelSize($settings["image_size_x"], $settings["image_size_y"]);
if ($settings["graphics_module"] == "gd") {
	$canvas_factory = new OsmMetaEmitter\Graphics\GdCanvasFactory;
} elseif ($settings["graphics_module"] == "imagick") {
	$canvas_factory = new OsmMetaEmitter\Graphics\ImagickCanvasFactory;
} else {
	throw new Exception("unknown graphics module $settings[graphics_module]");
}
$image_writer = new OsmMetaEmitter\Image\Writer($client, $settings["osm_tile_url"], $image_size, $canvas_factory, $settings["image_crosshair"]);

if ($settings["element_pages"]) {
	$web_page_writer = new OsmMetaEmitter\WebPage\Writer(
		(@$_SERVER['HTTPS'] ? "https" : "http") . "://$_SERVER[HTTP_HOST]${root}",
		$settings["osm_web_url"], $settings["site_name"], $settings["site_description"]
	);
} else {
	$web_page_writer = null;
}

$router = new OsmMetaEmitter\Router($loader, $image_writer, $web_page_writer, $settings["site_logo"]);
$router->route($request);
