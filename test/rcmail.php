<?php


/**
 * @internal Test class that does not exist anywhere
 */
class rcmail_config {

	private $data = [];

	public function get($keyname) {
		return $this->data[$keyname];
	}

	/**
	 * @internal added method for testing purposes
	 */
	public function set($keyname, $value) {
		$this->data[$keyname] = $value;
	}
}

/**
 * @internal Test class to mock Roundcube
 */
class rcmail
{
	/**
	 * @var rcmail_config
	 */
	public $config;

	/**
	 * @var rcmail_output
	 */
	public $output;

	/**
	 * @var self|null
	 */
	public static $instance = null;

	public function __construct()
	{
		$this->config = new rcmail_config();
		$this->output = new rcmail_output();
	}

	public static function get_instance() {
		if (static::$instance === null) {
			static::$instance = new self();
		}

		return static::$instance;
	}
}

class rcmail_output
{
	public function abs_url($path)
	{
		return '/' . ltrim($path, '/');
	}
}
