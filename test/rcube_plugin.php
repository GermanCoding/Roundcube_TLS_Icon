<?php

/**
 * @internal Test class to mock Roundcube
 */
class rcube_plugin
{
	public $urlbase = 'plugins/tls_icon/';

	public function url($path) {
		return rtrim($this->urlbase, '/') . '/' . ltrim($path, '/');
	}

	public function gettext($label) {
		global $labels;
		require_once __DIR__ . '/../localization/en_US.inc';
		return $labels[$label];
	}

	public function load_config() {
	}

	public function add_hook($name, $params) {
	}

	public function include_stylesheet($name) {
	}

	public function add_texts($folderName) {
	}
}
