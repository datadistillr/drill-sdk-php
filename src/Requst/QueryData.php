<?php

namespace thedataist\Drill\Requst;

/**
 * Query Data POST request object
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.0
 */
class QueryData extends RequestData {

	/**
	 * Query Type
	 * @var string $queryType
	 */
	public string $queryType;

	/**
	 * Query String
	 * @var string $query
	 */
	public string $query;

	/**
	 * Auto Limit value
	 * @var int $autoLimit
	 */
	public int $autoLimit;

	/**
	 * Options (associative array)
	 * @var array $options
	 */
	public array $options;


	/**
	 * QueryData Constructor
	 *
	 * @param string $query Query String
	 * @param int $autoLimit AutoLimit Results [default: 10000]
	 * @param ?array $options Associative Array of options, Using this will reset all default options
	 */
	public function __construct(string $query, int $autoLimit = 10000, ?array $options = null) {
		parent::__construct();

		$this->queryType = 'SQL';
		$this->query = $query;
		$this->autoLimit = $autoLimit;
		$this->options = $options ?? [
			'drill.exec.http.rest.errors.verbose' => true
		];
	}
}