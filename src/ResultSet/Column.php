<?php

namespace datadistillr\Drill\ResultSet;

/**
 * Class Column
 * @package thedataist\Drill
 * @author Tim Swagger <tim@renowne.com>
 */
class Column extends ResultSet {
	// region Properties

	/**
	 * Plugin Name
	 * @var string $plugin
	 */
	public string $plugin;

	/**
	 * Schema Name
	 * @var string $schema
	 */
	public string $schema;

	/**
	 * Table Name
	 * @var string $table_name
	 */
	public string $table_name;

	/**
	 * Column Name
	 * @var string $name
	 */
	public string $name;

	/**
	 * Data type
	 * @var string $data_type
	 */
	public string $data_type;

	/**
	 * Nullable
	 * @var bool $is_nullable
	 */
	public bool $is_nullable;

	// endregion

	/**
	 * Schema constructor.
	 * @param object|array $data
	 */
	public function __construct($data = null) {
		parent::__construct();

		$data = (object)$data;

		$this->plugin = $data->plugin ?? null;
		$this->schema = $data->schema ?? null;
		$this->table_name = $data->table_name ?? null;
		$this->name = $data->name ?? null;
		$this->data_type = $data->data_type ?? null;
		$this->is_nullable = isset($data->is_nullable) && ($data->is_nullable === true || $data->is_nullable === 'YES');
	}
}