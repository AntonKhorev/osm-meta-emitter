<?php namespace OsmMetaEmitter;

class Router {
	function __construct(
		private Osm\Loader $loader,
		private Image\Writer $image_writer,
		private ?WebPage\Writer $web_page_writer,
		private string $site_logo
	) {}

	function route(string $request): void {
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
}
