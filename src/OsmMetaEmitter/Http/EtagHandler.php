<?php namespace OsmMetaEmitter\Http;

class EtagHandler {
	private \DateTimeImmutable $start_timestamp;
	private ?string $main_etag = null;
	public ?array $tile_etags = null;
	public ?bool $can_skip_tiles = null;

	function __construct(
		public bool $enabled,
		private int $max_age,
		private int $settings_epoch_seconds
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

	function checkMainEtag(\DateTimeImmutable $element_timestamp): void {
		if (!$this->enabled || $this->main_etag === null) {
			$this->main_etag = null;
			$this->tile_etags = null;
			$this->can_skip_tiles = false;
			return;
		}

		$data_epoch_seconds = max($this->settings_epoch_seconds, $element_timestamp->getTimestamp());
		$cache_epoch_seconds = self::convertEtagToSeconds($this->main_etag);
		if ($cache_epoch_seconds < $data_epoch_seconds) {
			$this->main_etag = null;
			$this->tile_etags = null;
			$this->can_skip_tiles = false;
			return;
		}

		$cache_age_seconds = $this->start_timestamp->getTimestamp() - $cache_epoch_seconds;
		$this->can_skip_tiles = $cache_age_seconds < $this->max_age;
	}
	
	function sendEtagHeaders(?array $tile_etags): void {
		if ($this->enabled) {
			header("Cache-Control: max-age=$this->max_age, stale-while-revalidate=604800, stale-if-error=604800");
			if ($tile_etags !== null) {
				$main_etag = self::convertSecondsToEtag($this->start_timestamp->getTimestamp());
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

	private static function convertSecondsToEtag(int $seconds): string {
		return rtrim(base64_encode(pack("L", $seconds)), "=");
	}

	private static function convertEtagToSeconds(string $etag): int {
		return unpack("Lt", base64_decode($etag))["t"];
	}
}
