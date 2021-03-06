<?php

namespace datadistillr\Drill;

use datadistillr\Drill\Request\RequestUrl;
use datadistillr\Drill\Response\Response;
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

	/**
	 * Request URL (and metadata)
	 * @var RequestUrl $requestUrl
	 */
	protected RequestUrl $requestUrl;

	/**
	 * Original Response
	 * @var Response $response
	 */
	protected Response $response;

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
	 * @param Response $response Response Data from Drill Query
	 * @param string $query Query String
	 * @param RequestUrl $url Request URL
	 * @todo review if response should be sent as an array or object
	 */
	public function __construct(Response $response, string $query, RequestUrl $url) {
		$this->columns = $response->columns ?? [];
		$this->rows = $response->rows ?? [];
		$this->metadata = $response->metadata ?? [];
		$this->query = $query;
		$this->requestUrl = $url;
		$this->response = $response;

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

	/**
	 * Retrieve Schema Array
	 *
	 * @return array
	 * @todo possibly move this to another location
	 */
	public function getSchema(): array {
		return $this->schema;
	}

	/**
	 * Retrieve raw data rows
	 *
	 * @return array
	 */
	public function getRows(): array {
		return $this->rows;
	}

	/**
	 * Fetch column names from results
	 *
	 * @return array
	 */
	public function getColumns(): array {
		return $this->columns;
	}

	/**
	 * Get Raw Response
	 * @return Response
	 */
	public function getRawResponse(): Response {
		return $this->response;
	}

	/**
	 * Fetch First Result
	 *
	 * @return ?object First ResultSet item
	 */
	public function first(): ?object {
		if(isset($this->rows[0])) {
			return $this->rows[0];
		}
		return null;
	}

	/**
	 * Get number of fields
	 *
	 * @return int Number of fields
	 */
	public function fieldCount(): int {
		return count($this->columns);
	}

	/**
	 * Retrieve the number of resulting rows
	 *
	 * @return int Number of Rows
	 */
	public function numRows(): int {
		return count($this->rows);
	}

	// endregion
	// region Private Method


	// endregion
	// ---------------------------------------------------------------------------
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

	// endregion
}