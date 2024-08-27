<?php

// requires php with gd (php, php-gd packages)

// use this to serve for development purposes:
// php -S localhost:8001 debug-tile-server.php

require __DIR__ . "/src/OsmMetaEmitter/Settings.php";

$settings = OsmMetaEmitter\Settings::read();

openlog("osm-meta-emitter debug-tile-server", LOG_PERROR, LOG_USER);

if (preg_match("{/(\d+)/(\d+)/(\d+)\.png$}", $_SERVER['REQUEST_URI'], $matches)) {
	$z = $matches[1];
	$x = $matches[2];
	$y = $matches[3];
	syslog(LOG_INFO, "requested tile z = $z, x = $x, y = $y");

	sleep($settings["debug_tile_server_sleep"]);

	$image = imagecreatetruecolor(256, 256);
	$frame_color = imagecolorallocate($image, 128, 128, 128);
	$text_color = imagecolorallocate($image, 0, 255, 0);
	imagerectangle($image, 0, 0, 255, 255, $frame_color);
	imagestring($image, 5, 16, 16, "$z / $x / $y", $text_color);
	header("Content-Type: image/png");
	imagepng($image);
} else {
	syslog(LOG_INFO, "requested unrecognized path");

	header("HTTP/1.1 404 Not Found");
	header("Content-Type: text/plain");
	echo "not found\n";
}

syslog(LOG_INFO, print_r($_SERVER, true));
