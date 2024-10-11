<?php namespace OsmMetaEmitter;

class Router {
	static function fromSettings(array $settings, string $root_url): static {
		if ($settings["logger"] == "syslog") {
			$log_writer = new Log\SyslogWriter;
		} elseif ($settings["logger"] == "file") {
			$log_writer = new Log\FileWriter;
		} else {
			$log_writer = new Log\DisabledWriter;
		}

		if ($settings["osm_loader"] == "api") {
			$loader = new Osm\ApiLoader(
				new Http\Client(
					new Http\Logger($log_writer, $settings["log_outgoing_api_requests"])
				),
				$settings["osm_api_url"]
			);
		} elseif ($settings["osm_loader"] == "db") {
			$loader = new Osm\DbLoader($settings["db_dsn"], $settings["db_user"], $settings["db_password"]);
		} else {
			throw new \Exception("unknown osm data loader $settings[osm_loader]");
		}

		$image_size = new Image\IntPixelSize($settings["image_size_x"], $settings["image_size_y"]);
		if ($settings["graphics_module"] == "gd") {
			$canvas_factory = new Graphics\GdCanvasFactory;
		} elseif ($settings["graphics_module"] == "imagick") {
			$canvas_factory = new Graphics\ImagickCanvasFactory;
		} else {
			throw new \Exception("unknown graphics module $settings[graphics_module]");
		}

		if (is_numeric($settings["max_zoom"])) {
			$max_zoom_algorithm = new Osm\ConstantMaxZoomAlgorithm($settings["max_zoom"]);
		} elseif ($settings["max_zoom"] == "carto") {
			$max_zoom_algorithm = new Osm\CartoMaxZoomAlgorithm;
		} else {
			throw new \Exception("unknown max zoom algorithm $settings[max_zoom]");
		}

		$etag_handler = new Http\EtagHandler($settings["client_cache"], $settings["client_cache_max_age"], $settings["settings_epoch_seconds"]);
		$tile_loader = new Tile\Loader(
			new Http\Client(
				new Http\Logger($log_writer, $settings["log_outgoing_tile_requests"])
			),
			$settings["osm_tile_url"]
		);
		$image_writer = new Image\Writer(
			$etag_handler, $tile_loader,
			$image_size, $max_zoom_algorithm, $canvas_factory, $settings["image_crosshair"]
		);

		if ($settings["element_pages"]) {
			$web_page_writer = new WebPage\Writer(
				$root_url,
				$settings["osm_web_url"], $settings["site_name"], $settings["site_description"]
			);
		} else {
			$web_page_writer = null;
		}

		$incoming_logger = new Http\Logger($log_writer, $settings["log_incoming_requests"]);

		return new static($incoming_logger, $loader, $image_writer, $web_page_writer, $settings["site_logo"]);
	}

	function __construct(
		private Http\Logger $incoming_logger,
		private Osm\Loader $loader,
		private Image\Writer $image_writer,
		private ?WebPage\Writer $web_page_writer,
		private string $site_logo
	) {}

	function route(string $request): void {
		$this->log_incoming_http_request();
		if (preg_match("{^nodes?/(\d+)/image\.png?$}", $request, $match)) {
			$id = $match[1];
			try {
				$element = $this->loader->fetchNode($id);
				$this->image_writer->respondWithElementImage($element);
			} catch (Osm\Exception) {
				$this->respond_with_dummy_image();
			}
		} elseif (preg_match("{^ways?/(\d+)/image\.png?$}", $request, $match)) {
			$id = $match[1];
			try {
				$element = $this->loader->fetchWay($id);
				$this->image_writer->respondWithElementImage($element);
			} catch (Osm\Exception) {
				$this->respond_with_dummy_image();
			}
		} elseif (preg_match("{^relations?/(\d+)/image\.png?$}", $request, $match)) {
			$id = $match[1];
			try {
				$element = $this->loader->fetchRelation($id);
				$this->image_writer->respondWithElementImage($element);
			} catch (Osm\Exception) {
				$this->respond_with_dummy_image();
			}
		} elseif ($this->web_page_writer && preg_match("{^nodes?/(\d+)/?$}", $request, $match)) {
			$id = $match[1];
			$this->web_page_writer->respondWithNodePage($id);
		} elseif ($this->web_page_writer && preg_match("{^ways?/(\d+)/?$}", $request, $match)) {
			$id = $match[1];
			$this->web_page_writer->respondWithWayPage($id);
		} elseif ($this->web_page_writer && preg_match("{^relations?/(\d+)/?$}", $request, $match)) {
			$id = $match[1];
			$this->web_page_writer->respondWithRelationPage($id);
		} else {
			header("HTTP/1.1 404 Not Found");
			header("Content-Type: text/plain");
			echo "not found\n";
		}
	}

	private function respond_with_dummy_image(): void {
		header("Content-Type: image/png");
		readfile($this->site_logo);
	}

	private function log_incoming_http_request() {
		$items = ["$_SERVER[REQUEST_METHOD] $_SERVER[REQUEST_URI] $_SERVER[SERVER_PROTOCOL]"];
		foreach ($_SERVER as $key => $value) {
			if (!preg_match("/^HTTP_(.*)$/", $key, $match)) continue;
			$name = strtr(strtolower($match[1]), "_", "-");
			$items[] = "$name: $value";
		}
		$this->incoming_logger->logHttp("$_SERVER[REMOTE_ADDR] --> self", $items);
	}
}
