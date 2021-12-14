<?php

namespace datadistillr\Drill\Request;

/**
 * Plugin Data POST request object
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.0
 */
class PluginData extends RequestData {

	/**
	 * Plugin Name
	 * @var string $name
	 */
	public string $name;

	/**
	 * Config Value
	 * @var array $config
	 */
	public array $config;

	/**
	 * QueryData Constructor
	 *
	 * @param string $pluginName Plugin Name
	 * @param array $config Config Array
	 */
	public function __construct(string $pluginName, array $config = []) {
		parent::__construct();

		$this->name = $pluginName;
		$this->config = $config;
	}
}