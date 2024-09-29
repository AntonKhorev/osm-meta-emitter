<?php namespace OsmMetaEmitter\Osm;

/*

Create a user on an osm database:

	psql -d openstreetmap
	CREATE USER osm_meta_emitter WITH PASSWORD 'osm_meta_emitter_password';
	GRANT SELECT ON nodes, current_nodes TO osm_meta_emitter;

\du shows user list with the new user

*/

const DB_COORDS_SCALE = 10000000;

class DbLoader extends Loader {
	function __construct(
		private string $dsn,
		private string $user,
		private string $password
	) {}

	function fetchNode(int $id): Element {
		$dbh = $this->connect();
		$sth = $dbh->prepare("SELECT latitude, longitude, visible, version FROM current_nodes WHERE id = :id");
		$sth->execute(["id" => $id]);
		$node_row = $sth->fetch();
		if (!$node_row) throw new NotAvailableException("node #$id is not available");
		if ($node_row["visible"]) return $this->makeNodeFromRow($node_row);

		// deleted node may have latitude and longitude but we can't use them because they could be redacted
		$previous_version = $node_row["version"] - 1;
		$sth = $dbh->prepare("SELECT latitude, longitude, visible FROM nodes WHERE node_id = :id AND version = :version AND visible AND redaction_id IS NULL");
		$sth->execute(["id" => $id, "version" => $previous_version]);
		$node_row = $sth->fetch();
		if (!$node_row) throw new NotAvailableException("node #$id is not available when requesting a previous version");
		$node = $this->makeNodeFromRow($node_row);
		$node->visible = false;
		return $node;
	}

	function fetchWay(int $id): Element {
		throw new Exception("way fetching not implemented");
	}

	function fetchRelation(int $id): Element {
		throw new Exception("relation fetching not implemented");
	}

	private function connect(): \PDO {
		return new \PDO($this->dsn, $this->user, $this->password, [\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
	}

	private function makeNodeFromRow(array $row): Element {
		$lat = $row["latitude"] / DB_COORDS_SCALE;
		$lon = $row["longitude"] / DB_COORDS_SCALE;
		$point = new Point(NormalizedCoords::fromLatLon($lat, $lon));
		return new Element($point);
	}
}
