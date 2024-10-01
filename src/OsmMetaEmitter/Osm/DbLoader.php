<?php namespace OsmMetaEmitter\Osm;

/*

Create a user on an osm database:

	psql -d openstreetmap
	CREATE USER osm_meta_emitter WITH PASSWORD 'osm_meta_emitter_password';
	GRANT SELECT ON nodes, node_tags, current_nodes, current_node_tags, current_ways, current_way_nodes, current_way_tags, current_relations, current_relation_members, current_relation_tags TO osm_meta_emitter;

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
		if ($node_row["visible"]) {
			return new Element(
				new Point($this->makeNormalizedCoordsFromRow($node_row)),
				$this->loadMaxZoom($dbh, "node", $id)
			);
		}

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
		$node = new Element(
			new Point($this->makeNormalizedCoordsFromRow($node_row)),
			$this->loadMaxZoom($dbh, "node", $id, $previous_version)
		);
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
		return new Element(
			new LineString(...$way_coords),
			$this->loadMaxZoom($dbh, "way", $id)
		);
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

		$sth = $dbh->prepare("
			SELECT id, visible
			FROM current_relation_members JOIN current_ways ON member_id = id
			WHERE relation_id = :id AND member_type = 'Way'
		");
		$sth->execute(["id" => $id]);
		$way_ids = [];
		while ($way_row = $sth->fetch()) {
			if (!$way_row["visible"]) throw new InvisibleMemberException("relation #$id contains invisible way #$way_row[id]");
			$way_ids[] = $way_row["id"];
		}
		$lines = $this->loadWayLines($dbh, $id, $way_ids);

		return new Element(
			new GeometryCollection(...$points, ...$lines),
			$this->loadMaxZoom($dbh, "relation", $id)
		);
	}

	private function connect(): \PDO {
		return new \PDO($this->dsn, $this->user, $this->password, [\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
	}

	private function loadWayLines(\PDO $dbh, int $id, array $way_ids): array {
		if (count($way_ids) <= 0) return [];

		$in_placeholders = str_repeat("?,", count($way_ids) - 1) . "?";
		$sth = $dbh->prepare("
			SELECT latitude, longitude, visible, way_id
			FROM current_way_nodes JOIN current_nodes ON node_id = id
			WHERE way_id IN ($in_placeholders)
			ORDER BY way_id, sequence_id
		");
		$sth->execute($way_ids);
		$lines = [];
		$line_coords = [];
		$line_way_id = null;
		while ($node_row = $sth->fetch()) {
			if (!$node_row["visible"]) throw new InvisibleMemberException("relation #$id contains way #$node_row[way_id] with invisible nodes");
			if ($line_way_id !== $node_row["way_id"]) {
				if ($line_way_id !== null) $lines[] = new LineString(...$line_coords);
				$line_coords = [];
				$line_way_id = $node_row["way_id"];
			}
			$line_coords[] = $this->makeNormalizedCoordsFromRow($node_row);
		}
		$lines[] = new LineString(...$line_coords);
		return $lines;
	}

	private function loadMaxZoom(\PDO $dbh, string $type, int $id, ?int $version = null): int {
		$table_name = $type . "_tags";
		$element_condition = $type . "_id = :id";
		$bindings = ["id" => $id];
		if ($version !== null) {
			$element_condition .= " AND version = :version";
			$bindings["version"] = $version;
		} else {
			$table_name = "current_" . $table_name;
		}
		$sth = $dbh->prepare("SELECT v FROM $table_name WHERE $element_condition AND k = 'amenity'");
		$sth->execute($bindings);
		$row = $sth->fetch();
		if (in_array(@$row['v'], Element::AMENITY_MAX_ZOOM_TAG_VALUES)) return Element::AMENITY_MAX_ZOOM;
		return Element::DEFAULT_MAX_ZOOM;
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
