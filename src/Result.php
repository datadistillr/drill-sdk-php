<?php

namespace thedataist\Drill;

use stdClass;

/**
 * @package Drill
 * @author Charles Givre <cgivre@thedataist.com>
 * @author Tim Swagger <tim@datadistillr.com>
 *
 * @since 0.6.0 Updated method/variable names to use camelCase
 */
class Result {
	// region Properties

	/**
	 * Column Array
	 * @var array $columns
	 */
	protected array $columns;

	/**
	 * Results Rows
	 * @var array $rows
	 */
	protected array $rows;

	/**
	 * Query String
	 * @var string $query
	 */
	protected string $query;

	/**
	 * Row Pointer
	 *
	 * @todo expand this definition
	 * @var int $rowPointer
	 */
	protected int $rowPointer;

	/**
	 * Meta Data
	 * @var string[] $metadata
	 */
	protected array $metadata;

	/**
	 * Schema List
	 * @var array
	 * @todo identify actual datatype
	 */
	protected array $schema = [];

	// endregion
	// region Static Functions

	/**
	 * Cleans the data types and specifically removes precision information
	 * from VARCHAR and DECIMAL data types which is not useful for UI work.
	 *
	 * @param string $dataType The string data type which should be a Drill MinorType
	 *
	 * @return string The datatype without precision information
	 */
	static public function cleanDataTypeName(string $dataType): string {
		$pattern = "/[a-zA-Z]+\(\d+(,\s*\d+)?\)/";
		if (preg_match($pattern, $dataType)) {
			$parts = explode('(', $dataType);
			$cleanDataType = $parts[0];
		} else {
			$cleanDataType = $dataType;
		}

		return $cleanDataType;
	}

	// endregion
	// region Constructor

	/**
	 * Result constructor.
	 *
	 * @param array $response Response Data from Drill Query
	 * @param string $query Query String
	 * @todo review if response should be sent as an array or object
	 */
	public function __construct(array $response, string $query) {
		$this->columns = $response['columns'] ?? [];
		$this->rows = $response['rows'] ?? [];
		$this->metadata = $response['metadata'] ?? [];
		$this->query = $query;
		$this->rowPointer = 0;

		for ($i = 0; $i < count($this->columns); $i++) {
			$info = [
				'column' => $this->columns[$i],
				'data_type' => self::cleanDataTypeName($this->metadata[$i])
			];
			$this->schema[] = $info;
		}
	}

	// endregion
	// region Primary Class Methods

	function data_seek($n) {
		if (!is_int($n)) {
			return false;
		} elseif ($n > count($this->rows)) {
			return false;
		} else {
			$this->rowPointer = $n;
			return true;
		}
	}

	/**
	 * Fetch results as an associative array
	 *
	 * @return array
	 */
	public function fetchAssoc(): array {
		if ($this->rowPointer >= count($this->rows)) {
			return [];
		} else {
			$result = $this->rows[$this->rowPointer];
			$this->rowPointer++;
			return $result;
		}
	}

	/**
	 * Retrieve Schema Array
	 *
	 * @return array
	 */
	public function getSchema(): array {
		return $this->schema;
	}

	/**
	 * Retrieve Data Rows
	 *
	 * @return array
	 */
	public function getRows(): array {
		return $this->rows;
	}

	/**
	 * Alias to fetch method
	 *
	 * @see fetch()
	 * @return ?stdClass
	 */
	public function fetchObject(): ?stdClass {
		return $this->fetch();
	}

	/**
	 * Fetch Results
	 *
	 * @return ?stdClass
	 */
	function fetch(): ?stdClass {
		if ($this->rowPointer >= count($this->rows)) {
			return null;
		} else {
			$result = $this->rows[$this->rowPointer];
			$resultObject = new stdClass();
			foreach ($result as $key => $value) {
				$resultObject->$key = $value;
			}
			$this->rowPointer++;
			return $resultObject;
		}
	}

	/**
	 * Fetch column names from results
	 *
	 * @return array
	 */
	function getColumns(): array {
		return $this->columns ?? [];
	}

	/**
	 * Get number of fields
	 *
	 * @return int Number of fields
	 */
	function fieldCount(): int {
		return count($this->columns);
	}

	/**
	 * Check if there are results beyond the current row index
	 *
	 * @return bool
	 */
	public function hasMoreResults(): bool {
		return $this->rowPointer < count($this->rows);
	}

	/**
	 * Retrieve the number of resulting rows
	 *
	 * @return int Number of Rows
	 */
	function numRows(): int {
		return count($this->rows);
	}

	// endregion
	// region Deprecated Methods

	/**
	 * Cleans the data types and specifically removes precision information
	 * from VARCHAR and DECIMAL data types which is not useful for UI work.
	 *
	 * @param string $dataType The string data type which should be a Drill MinorType
	 *
	 * @return string The datatype without precision information
	 * @deprecated v0.6.0 use cleanDataTypeName()
	 */
	static function clean_data_type_name(string $dataType): string {
		return self::cleanDataTypeName($dataType);
	}


	/**
	 * Return Schema
	 *
	 * @return array
	 * @deprecated v0.6.0 use getSchema()
	 */
	function get_schema() {
		return $this->getSchema();
	}

	/**
	 * Fetch Associative Array
	 *
	 * @return array
	 * @deprecated v0.6.0 use fetchAssoc()
	 */
	function fetch_assoc(): array {
		return $this->fetchAssoc();
	}

	/**
	 * Alias to fetch method
	 *
	 * @see fetch()
	 * @return ?stdClass
	 * @deprecated v0.6.0 use fetchAssoc()
	 */
	function fetch_object(): ?stdClass {
		return $this->fetchObject();
	}

	/**
	 * Fetch all rows
	 *
	 * @return array
	 * @deprecated v0.6.0 use getRows()
	 */
	function fetch_all(): array {
		return $this->getRows();
	}

	/**
	 * Fetch column names from results
	 *
	 * @return array
	 * @deprecated v0.6.0 use getColumns()
	 */
	function fetch_columns(): array {
		return $this->getColumns();
	}

	/**
	 * Get number of fields
	 *
	 * @return int Number of fields
	 * @deprecated v0.6.0 use fieldCount()
	 */
	function field_count(): int {
		return $this->fieldCount();
	}

	/**
	 * Retrieve the number of resulting rows
	 *
	 * @return int Number of Rows
	 * @deprecated v0.6.0 use numRows()
	 */
	function num_rows(): int {
		return $this->numRows();
	}

	/**
	 * Check if there are results beyond the current row index
	 *
	 * @return bool
	 * @deprecated v0.6.0 use hasMoreResults()
	 */
	function more_results() {
		return $this->hasMoreResults();
	}

	// endregion
}