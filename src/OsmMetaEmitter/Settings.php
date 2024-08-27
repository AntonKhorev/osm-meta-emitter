<?php namespace OsmMetaEmitter;

class Settings {
	static function read(): array {
		$settings = [];
		static::readSettingsFile($settings, "settings.ini");
		static::readSettingsFile($settings, "settings.local.ini");
		return $settings;
	}

	private static function readSettingsFile(array &$settings, string $filename): void {
		$new_settings = @parse_ini_file($filename);
		if ($new_settings) {
			$settings = array_merge($settings, $new_settings);
		}
	}
}
