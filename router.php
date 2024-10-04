<?php

// requires php with gd or imagick (php, php-gd or php-imagick, php-curl packages and php-pgsql if db loader is used)

// use this to serve for development purposes:
// php -S localhost:8000 router.php

spl_autoload_register(function ($class_name) {
	$class_path = str_replace("\\", "/", $class_name);
	$filename = __DIR__ . "/src/" . $class_path . ".php";
	if (file_exists($filename)) require $filename;
});

$settings = OsmMetaEmitter\Settings::read();

if ($settings["logger"] == "syslog") {
	$logger = new OsmMetaEmitter\SyslogLogger;
} elseif ($settings["logger"] == "file") {
	$logger = new OsmMetaEmitter\FileLogger;
} else {
	$logger = new OsmMetaEmitter\DisabledLogger;
}
$disabled_logger = new OsmMetaEmitter\DisabledLogger;

if ($settings["log_incoming_http_requests"]) {
	log_incoming_http_request($logger);
}

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

$client = new OsmMetaEmitter\HttpClient($settings["log_outgoing_http_requests"] ? $logger : $disabled_logger);

if ($settings["osm_loader"] == "api") {
	$loader = new OsmMetaEmitter\Osm\ApiLoader($client, $settings["osm_api_url"]);
} elseif ($settings["osm_loader"] == "db") {
	$loader = new OsmMetaEmitter\Osm\DbLoader($settings["db_dsn"], $settings["db_user"], $settings["db_password"]);
} else {
	throw new Exception("unknown osm data loader $settings[osm_loader]");
}

$image_size = new OsmMetaEmitter\Image\IntPixelSize($settings["image_size_x"], $settings["image_size_y"]);
if ($settings["graphics_module"] == "gd") {
	$canvas_factory = new OsmMetaEmitter\Graphics\GdCanvasFactory;
} elseif ($settings["graphics_module"] == "imagick") {
	$canvas_factory = new OsmMetaEmitter\Graphics\ImagickCanvasFactory;
} else {
	throw new Exception("unknown graphics module $settings[graphics_module]");
}

if (is_numeric($settings["max_zoom"])) {
	$max_zoom_algorithm = new OsmMetaEmitter\Osm\ConstantMaxZoomAlgorithm($settings["max_zoom"]);
} elseif ($settings["max_zoom"] == "carto") {
	$max_zoom_algorithm = new OsmMetaEmitter\Osm\CartoMaxZoomAlgorithm;
} else {
	throw new Exception("unknown max zoom algorithm $settings[max_zoom]");
}

$client_cache_handler = new OsmMetaEmitter\ClientCacheHandler($settings["client_cache"]);
$tile_loader = new OsmMetaEmitter\Tile\Loader($client, $settings["osm_tile_url"]);
$image_writer = new OsmMetaEmitter\Image\Writer(
	$client_cache_handler, $tile_loader,
	$image_size, $max_zoom_algorithm, $canvas_factory, $settings["image_crosshair"]
);

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

function log_incoming_http_request(OsmMetaEmitter\Logger $logger) {
	$items = ["$_SERVER[REQUEST_METHOD] $_SERVER[REQUEST_URI] $_SERVER[SERVER_PROTOCOL]"];
	foreach ($_SERVER as $key => $value) {
		if (!preg_match("/^HTTP_(.*)$/", $key, $match)) continue;
		$name = strtr(strtolower($match[1]), "_", "-");
		$items[] = "$name: $value";
	}
	$logger->logHttp("client --> self", $items);
}
