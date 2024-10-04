<?php namespace OsmMetaEmitter;

class ClientCacheHandler {
	private \DateTimeImmutable $start_timestamp;
	private ?string $main_etag = null;
	public ?array $tile_etags = null;
	public ?bool $can_skip_tiles = null;

	function __construct(
		public bool $enabled,
		private int $max_age
	) {
		$this->start_timestamp = new \DateTimeImmutable;
		if (!$this->enabled) return;
		@$input_etag_string = $_SERVER["HTTP_IF_NONE_MATCH"];
		if (!$input_etag_string) return;
		if (!preg_match('/"v1:(.*)"/', $input_etag_string, $match)) return;
		$all_etags = explode(":", $match[1]);
		$this->main_etag = array_shift($all_etags);
		$this->tile_etags = $all_etags;
	}

	function checkMainEtag(\DateTimeImmutable $main_timestamp): void {
		if (!$this->enabled || $this->main_etag === null) {
			$this->main_etag = null;
			$this->tile_etags = null;
			$this->can_skip_tiles = false;
			return;
		}

		$cache_epoch_seconds = unpack("Lt", base64_decode($this->main_etag))["t"];
		if ($cache_epoch_seconds < $main_timestamp->getTimestamp()) {
			$this->main_etag = null;
			$this->tile_etags = null;
			$this->can_skip_tiles = false;
			return;
		}

		$cache_age_seconds = $this->start_timestamp->getTimestamp() - $cache_epoch_seconds;
		$this->can_skip_tiles = $cache_age_seconds < $this->max_age;
	}
	
	function sendEtagHeaders(\DateTimeImmutable $main_timestamp, ?array $tile_etags): void {
		if ($this->enabled) {
			header("Cache-Control: max-age=$this->max_age, stale-while-revalidate=604800, stale-if-error=604800");
			if ($tile_etags !== null) {
				$main_etag = $this->computeMainEtag(max($this->start_timestamp, $main_timestamp));
				$combined_tile_etags = implode(":", $tile_etags);
				header("ETag: \"v1:$main_etag:$combined_tile_etags\"");
			}
		}
	}
	
	function sendNotModifiedHeaders(): void {
		if ($this->enabled) {
			header("HTTP/1.1 304 Not Modified");
		} else {
			throw new \Exception("attempted to send 'not modified' while not using client cache");
		}
	}

	private function computeMainEtag(\DateTimeImmutable $main_timestamp): string {
		return rtrim(base64_encode(pack("L", $main_timestamp->getTimestamp())), "=");
	}
}
