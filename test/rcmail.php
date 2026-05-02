<?php


/**
 * @internal Test class that does not exist anywhere
 */
class rcmail_config
{

	private $data = [];

	public function get($keyname)
	{
		return $this->data[$keyname];
	}

	/**
	 * @internal added method for testing purposes
	 */
	public function set($keyname, $value)
	{
		$this->data[$keyname] = $value;
	}
}

/**
 * @internal Test class to mock Roundcube output handling
 */
class rcmail_output
{
	/**
	 * @var callable|null
	 */
	private $asset_url_callback = null;

	public function asset_url($path)
	{
		if ($this->asset_url_callback !== null) {
			return call_user_func($this->asset_url_callback, $path);
		}

		return $path;
	}

	/**
	 * @internal added method for testing purposes
	 */
	public function set_asset_url_callback($callback)
	{
		$this->asset_url_callback = $callback;
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

	public static function get_instance()
	{
		if (static::$instance === null) {
			static::$instance = new self();
		}

		return static::$instance;
	}
}
