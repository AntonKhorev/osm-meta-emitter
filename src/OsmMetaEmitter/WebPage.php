<?php namespace OsmMetaEmitter;

class WebPage {
	function __construct(
		private string $root_url,
		private string $osm_web_url,
		private string $site_name,
		private string $site_description
	) {}

	function respond_with_node_page(int $id): void {
		$this->respond_with_element_page("node/$id", "Node: $id");
	}

	function respond_with_way_page(int $id): void {
		$this->respond_with_element_page("way/$id", "Way: $id");
	}

	private function respond_with_element_page(string $path, string $title): void {
		$osm_url = $this->osm_web_url . $path;
		$image_url = $this->root_url . "$path/image.png";
	
		echo "<!DOCTYPE html>\n";
		echo "<html lang=en>\n";
		echo "<head>\n";
		echo $this->meta_tag("og:site_name", $this->site_name);
		echo $this->meta_tag("og:title", $title);
		echo $this->meta_tag("og:type", "website");
		echo $this->meta_tag("og:url", $osm_url);
		echo $this->meta_tag("og:description", $this->site_description);
		echo $this->meta_tag("og:image", $image_url);
		echo $this->meta_tag("og:image:alt", "Node location");
		echo "</head>\n";
		echo "<body>\n";
		echo "<h1>" . htmlspecialchars($title) . "</h1>\n";
		echo "<p>See on <a href='" . htmlspecialchars($osm_url) . "'>" . htmlspecialchars($this->site_name) . "</a></p>\n";
		echo "</body>\n";
		echo "</html>\n";
	}
	
	private function meta_tag(string $property, string $content): string {
		return "<meta property='" . htmlspecialchars($property) . "' content='" . htmlspecialchars($content) . "'>\n";
	}
}