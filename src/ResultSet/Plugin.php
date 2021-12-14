<?php

namespace datadistillr\Drill\ResultSet;

/**
 * Class Plugin
 * @package thedataist\Drill
 * @author Tim Swagger <tim@renowne.com>
 */
class Plugin extends ResultSet {

	// region Properties
	/**
	 * Plugin Name
	 * @var string $name
	 */
	public string $name;

	/**
	 * List of Schemas for the current Plugin
	 * @var Schema[] $schemas
	 */
	public array $schemas = array();
	// endregion

	/**
	 * Schema constructor.
	 * @param object|array $data
	 */
	public function __construct($data = null) {
		parent::__construct();

		$data = (object)$data;

		$this->name = $data->name ?? null;
		$this->schemas = $data->schemas ?? null;
	}
}