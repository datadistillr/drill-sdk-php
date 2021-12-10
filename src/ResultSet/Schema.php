<?php


namespace thedataist\Drill\ResultSet;

/**
 * Class Schema
 * @package thedataist\Drill
 * @author Tim Swagger <tim@renowne.com>
 */
class Schema extends ResultSet {

	// region Properties
	/**
	 * Name of parent plugin/dataSource
	 * @var string $plugin
	 */
	public string $plugin;

	/**
	 * Name of Schema
	 * @var string $name
	 */
	public string $name;

	/**
	 * List of tables in schema
	 * @var array $tables
	 */
	public array $tables = [];
	// endregion

	/**
	 * Schema constructor.
	 * @param object|array $data
	 */
	public function __construct($data = null) {
		parent::__construct();

		$data = (object)$data;

		$this->plugin = $data->plugin;
		$this->name = $data->name;
	}
}