<?php namespace OsmMetaEmitter\Http;

class Logger {
	function __construct(
		private \OsmMetaEmitter\Log\Writer $log_writer,
		private bool|string $verbosity
	) {}

	function logHttp(string $title, array $headers, ?int $size = null, $timestamp = new \DateTimeImmutable): void {
		if (!$this->verbosity) return;

		$message = "";
		$message .= "$title (\n";
		$skipped_headers_count = 0;
		foreach ($headers as $header) {
			if ($this->verbosity == "full" || $this->is_important_header($header)) {
				$message .= "    $header\n";
			} else {
				$skipped_headers_count++;
			}
		}
		if ($skipped_headers_count > 0) {
			$message .= "    (+ $skipped_headers_count headers)\n";
		}
		$message .= ")";
		if ($size !== null) {
			$message .= " $size bytes";
		}
		$this->log_writer->log($message, $timestamp);
	}

	private function is_important_header(string $header): bool {
		if (!preg_match('/^\s*([^:]+):/', $header, $matches)) {
			return true;
		}
		$name = strtolower($matches[1]);
		return in_array($name, [
			"cache-control", "etag", "if-none-match"
		]);
	}
}
