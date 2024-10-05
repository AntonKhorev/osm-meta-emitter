<?php namespace OsmMetaEmitter\Tile;

class Loader {
	function __construct(
		private \OsmMetaEmitter\Http\Client $client,
		private string $osm_tile_url,
	) {}

	function load(callable $process_loaded_tile, LoadEntry ...$load_entries): void {
		// check if can afford to do if-none-match requests
		$all_with_etags = true;
		foreach ($load_entries as $i => $load_entry) {
			if ($load_entry->etag === null) {
				$all_with_etags = false;
				break;
			}
		}

		// do if-none-match requests if etag of every entry is known
		$modified_at_i = null;
		if ($all_with_etags) {
			foreach ($load_entries as $i => $load_entry) {
				$response = $this->client->fetchWithEtag($this->osm_tile_url . $load_entry->path, 15, $load_entry->etag);
				if ($response) {
					if ($response->body !== null) {
						$modified_at_i = $i;
						$process_loaded_tile($i, $response->body);
						break;
					}
				} // ignore errors because it's possible to reuse cached tile
			}
		}

		// do requests without etags if one of etag matches failed or etags weren't used
		$with_errors = false;
		if (!$all_with_etags || $modified_at_i !== null) {
			foreach ($load_entries as $i => $load_entry) {
				if ($i === $modified_at_i) continue;
				$response = $this->client->fetchWithEtag($this->osm_tile_url . $load_entry->path, 15);
				if ($response) {
					$process_loaded_tile($i, $response->body);
					$load_entry->etag = $response->etag;
				} else {
					$with_errors = true;
				}
			}
		}

		// remove etags in case some tiles were skipped because of errors
		if ($with_errors) {
			foreach ($load_entries as $i => $load_entry) {
				$load_entry->etag = null;
			}
		}
	}
}
