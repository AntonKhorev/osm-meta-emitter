<?php namespace OsmMetaEmitter\Graphics;

class Color {
	private ?array $rgb = null;

	function __construct(
		public readonly string $value
	) {}

	function getRgb(): array {
		if ($this->rgb === null) $this->decode();
		return $this->rgb;
	}

	function getRed(): int {
		if ($this->rgb === null) $this->decode();
		return $this->rgb[0];
	}

	function getGreen(): int {
		if ($this->rgb === null) $this->decode();
		return $this->rgb[1];
	}

	function getBlue(): int {
		if ($this->rgb === null) $this->decode();
		return $this->rgb[2];
	}

	private function decode(): void {
		if (!preg_match("/^#([a-fA-F0-9]+)$/", $this->value, $match)) {
			throw new \Exception("can't decode color value $this->value");
		}
		$hex = $match[1];
		if (strlen($hex) == 3) {
			$this->rgb = [hexdec($hex[0] . $hex[0]), hexdec($hex[1] . $hex[1]), hexdec($hex[2] . $hex[2])];
		} elseif (strlen($hex) == 6) {
			$this->rgb = [hexdec($hex[0] . $hex[1]), hexdec($hex[2] . $hex[3]), hexdec($hex[4] . $hex[5])];
		} else {
			throw new \Exception("can't decode color value $this->value");
		}
	}
}
