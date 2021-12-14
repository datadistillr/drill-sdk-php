<?php

namespace datadistillr\Drill\ResultSet;

/**
 * Class Table
 * @package thedataist\Drill
 * @author Tim Swagger <tim@renowne.com>
 */
class Table extends ResultSet {

	// region Properties
	/**
	 * Name of parent schema/database
	 * @var string $schema
	 */
	public string $schema;

	/**
	 * Name of Table
	 * @var string $name
	 */
	public string $name;

	/**
	 * List of columns in table
	 * @var Column[] $columns
	 */
	public array $columns = [];
	// endregion

	/**
	 * Schema constructor.
	 * @param object|array $data
	 */
	public function __construct($data = null) {
		parent::__construct();

		$data = (object)$data;

		$this->schema = $data->schema ?? null;
		$this->name = $data->name ?? null;
	}

}