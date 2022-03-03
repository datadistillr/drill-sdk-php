<?php

namespace datadistillr\Drill\Response;

/**
 * Drill Query Response Class
 *
 * This is the class for handling all drill query responses
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.2
 */
class QueryResponse extends Response {

	/**
	 * Query ID
	 * @var string $queryId
	 */
	public string $queryId;

	/**
	 * Columns
	 * @var string[] $columns Column Names
	 */
	public array $columns;

	/**
	 * Meta Data for each column
	 * @var string[] $metadata
	 */
	public array $metadata;

	/**
	 * Attempted Auto Limit
	 * @var int $attemptedAutoLimit
	 */
	public int $attemptedAutoLimit;

	/**
	 * Rows
	 * @var array $rows
	 */
	public array $rows;

	/**
	 * Query State
	 * @var string $queryState
	 */
	public string $queryState;

	/**
	 * Error Message
	 * @var string $errorMessage
	 */
	public string $errorMessage;

	/**
	 * Stack Trace
	 * @var ?string[] $stackTrace
	 */
	public ?array $stackTrace = null;


	/**
	 * Constructor class
	 *
	 * @param object $responseObject Base Object
	 */
	public function __construct(object $responseObject) {
		parent::__construct($responseObject);
	}
}