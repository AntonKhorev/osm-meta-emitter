<?php

// requires php with gd (php, php-gd packages)

// use this to serve for development purposes:
// php -S localhost:8001 debug-tile-server.php

openlog("osm-og-image debug-tile-server", LOG_PERROR, LOG_USER);

if (preg_match("{/(\d+)/(\d+)/(\d+)\.png$}", $_SERVER['REQUEST_URI'], $matches)) {
	$z = $matches[1];
	$x = $matches[2];
	$y = $matches[3];
	syslog(LOG_INFO, "requested tile z = $z, x = $x, y = $y");

	$image = imagecreatetruecolor(256, 256);
	$text_color = imagecolorallocate($image, 0, 255, 0);
	imagestring($image, 5, 0, 0, "$z / $x / $y", $text_color);
	header("Content-Type: image/png");
	imagepng($image);
} else {
	syslog(LOG_INFO, "requested unrecognized path");

	header("HTTP/1.1 404 Not Found");
	header("Content-Type: text/plain");
	echo "not found";
}

syslog(LOG_INFO, print_r($_SERVER, true));
