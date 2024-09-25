<?php namespace OsmMetaEmitter\Osm;

abstract class Loader {
	abstract function fetchNode(int $id): Element;
	abstract function fetchWay(int $id): Element;
	abstract function fetchRelation(int $id): Element;
}
