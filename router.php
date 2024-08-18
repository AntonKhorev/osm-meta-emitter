<?php

// requires php with gd (php, php-gd, php-curl packages)

// use this to serve for development purposes:
// php -S localhost:8000 router.php

$config = [
	// "osm_api_url" => "https://api.openstreetmap.org/",
	"osm_api_url" => "http://127.0.0.1:3000/",
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

if ($request == "testimage") {
	// $image_url = "https://tile.openstreetmap.org/14/9571/4762.png";
	$image_url = "http://127.0.0.1:8001/14/9571/4762.png";

	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $image_url);
	curl_setopt($ch, CURLOPT_USERAGENT, "osm-og-image curl/" . curl_version()["version"]);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$image_data = curl_exec($ch);
	curl_close($ch);

	// $image = imagecreatetruecolor(256, 256);
	$image = imagecreatefromstring($image_data);
	header("Content-Type: image/png");
	imagepng($image);
} elseif ($config['element_pages'] && preg_match("{^nodes?/(\d+)/?}", $request, $match)) {
	header("Content-Type: text/plain");
	$id = $match[1];
	echo "requested node #$id\n";
	$node_url = "$config[osm_api_url]api/0.6/node/$id.json";

	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $node_url);
	curl_setopt($ch, CURLOPT_USERAGENT, "osm-og-image curl/" . curl_version()["version"]);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$element_response_string = curl_exec($ch);
	curl_close($ch);

	$element_response = json_decode($element_response_string);
	$node = $element_response->elements[0];
	print_r($node);
} else {
	header("HTTP/1.1 404 Not Found");
	header("Content-Type: text/plain");
	echo "not found\n";
}
