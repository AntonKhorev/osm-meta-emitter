<?php namespace OsmMetaEmitter\Osm;

/*

Create a user on an osm database:

	psql -d openstreetmap
	CREATE USER osm_meta_emitter WITH PASSWORD 'osm_meta_emitter_password';
	GRANT SELECT ON nodes, current_nodes, current_ways, current_way_nodes, current_relations, current_relation_members TO osm_meta_emitter;

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
		$sth = $dbh->prepare("
			SELECT latitude, longitude, visible, version
			FROM current_nodes
			WHERE id = :id
		");
		$sth->execute(["id" => $id]);
		$node_row = $sth->fetch();
		if (!$node_row) throw new NotAvailableException("node #$id is not available");
		if ($node_row["visible"]) return $this->makeNodeFromRow($node_row);

		// deleted node may have latitude and longitude but we can't use them because they could be redacted
		$previous_version = $node_row["version"] - 1;
		$sth = $dbh->prepare("
			SELECT latitude, longitude, visible
			FROM nodes
			WHERE node_id = :id AND version = :version AND visible AND redaction_id IS NULL
		");
		$sth->execute(["id" => $id, "version" => $previous_version]);
		$node_row = $sth->fetch();
		if (!$node_row) throw new NotAvailableException("node #$id is not available when requesting a previous version");
		$node = $this->makeNodeFromRow($node_row);
		$node->visible = false;
		return $node;
	}

	function fetchWay(int $id): Element {
		$dbh = $this->connect();
		$sth = $dbh->prepare("SELECT visible FROM current_ways WHERE id = :id AND visible");
		$sth->execute(["id" => $id]);
		$way_row = $sth->fetch();
		if (!$way_row) throw new NotAvailableException("way #$id is not available");

		$sth = $dbh->prepare("
			SELECT latitude, longitude, visible
			FROM current_way_nodes JOIN current_nodes ON node_id = id
			WHERE way_id = :id
			ORDER BY sequence_id
		");
		$sth->execute(["id" => $id]);
		$way_coords = [];
		while ($node_row = $sth->fetch()) {
			if (!$node_row["visible"]) throw new InvisibleMemberException("way #$id contains invisible nodes");
			$way_coords[] = $this->makeNormalizedCoordsFromRow($node_row);
		}
		$line = new LineString(...$way_coords);
		return new Element($line);
	}

	function fetchRelation(int $id): Element {
		$dbh = $this->connect();
		$sth = $dbh->prepare("SELECT visible FROM current_relations WHERE id = :id AND visible");
		$sth->execute(["id" => $id]);
		$relation_row = $sth->fetch();
		if (!$relation_row) throw new NotAvailableException("relation #$id is not available");

		$sth = $dbh->prepare("
			SELECT latitude, longitude, visible
			FROM current_relation_members JOIN current_nodes ON member_id = id
			WHERE relation_id = :id AND member_type = 'Node'
		");
		$sth->execute(["id" => $id]);
		$points = [];
		while ($node_row = $sth->fetch()) {
			if (!$node_row["visible"]) throw new InvisibleMemberException("relation #$id contains invisible nodes");
			$points[] = new Point($this->makeNormalizedCoordsFromRow($node_row));
		}
		$geometry = new GeometryCollection(...$points);
		return new Element($geometry);
	}

	private function connect(): \PDO {
		return new \PDO($this->dsn, $this->user, $this->password, [\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
	}

	private function makeNodeFromRow(array $row): Element {
		$point = new Point($this->makeNormalizedCoordsFromRow($row));
		return new Element($point);
	}

	private function makeNormalizedCoordsFromRow(array $row): NormalizedCoords {
		$lat = $row["latitude"] / DB_COORDS_SCALE;
		$lon = $row["longitude"] / DB_COORDS_SCALE;
		return NormalizedCoords::fromLatLon($lat, $lon);
	}
}
