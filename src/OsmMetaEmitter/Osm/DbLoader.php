<?php namespace OsmMetaEmitter\Osm;

/*

Create a user on an osm database:

	psql -d openstreetmap
	CREATE USER osm_meta_emitter WITH PASSWORD 'osm_meta_emitter_password';
	GRANT SELECT ON current_nodes TO osm_meta_emitter;

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
		$sth = $dbh->prepare("SELECT latitude, longitude FROM current_nodes WHERE id = :id");
		$sth->execute(["id" => $id]);
		$node_row = $sth->fetch();
		if (!$node_row) throw new NotAvailableException("node #$id is not available");
		$lat = $node_row["latitude"] / DB_COORDS_SCALE;
		$lon = $node_row["longitude"] / DB_COORDS_SCALE;
		$point = new Point(NormalizedCoords::fromLatLon($lat, $lon));
		return new Element($point);
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
}
