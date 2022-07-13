<?php

/**
 * @internal Test class to mock Roundcube
 */
class rcube_plugin
{
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
