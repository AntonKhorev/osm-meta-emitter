<?php

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

echo "<div>Root: " . htmlspecialchars($root) . "</div>\n";
echo "<div>Request: " . htmlspecialchars($request) . "</div>\n";
