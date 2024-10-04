<?php namespace OsmMetaEmitter;

class Settings {
	static function read(): array {
		$settings = ["settings_epoch_seconds" => 0];
		static::readSettingsFile($settings, "settings.ini");
		static::readSettingsFile($settings, "settings.local.ini");
		return $settings;
	}

	private static function readSettingsFile(array &$settings, string $filename): void {
		$new_settings = @parse_ini_file($filename);
		if ($new_settings) {
			$settings = array_merge($settings, $new_settings);
			$settings["settings_epoch_seconds"] = max($settings["settings_epoch_seconds"], filemtime($filename));
		}
	}
}
