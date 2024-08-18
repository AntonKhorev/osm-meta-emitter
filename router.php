<?php

// requires php with gd (php, php-gd packages)

// use this to serve for development purposes:
// php -S localhost:8000 router.php

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

if ($request == "testimage") {
	$image = imagecreatetruecolor(256, 256);
	header("Content-type: image/png");
	imagepng($image);
} else {
	echo "<div>Root: " . htmlspecialchars($root) . "</div>\n";
	echo "<div>Request: " . htmlspecialchars($request) . "</div>\n";
}
