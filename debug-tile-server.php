<?php

// requires php with gd (php, php-gd packages)

// use this to serve for development purposes:
// php -S localhost:8001 debug-tile-server.php

if (preg_match("{/(\d+)/(\d+)/(\d+)\.png$}", $_SERVER['REQUEST_URI'], $matches)) {
	header("Content-Type: text/plain");
	$z = $matches[1];
	$x = $matches[2];
	$y = $matches[3];
	echo "requested tile z = $z, x = $x, y = $y";
} else {
	header("HTTP/1.1 404 Not Found");
	header("Content-Type: text/plain");
	echo "not found";
}
