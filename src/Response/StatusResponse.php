<?php

namespace datadistillr\Drill\Response;

/**
 * Drill Status Response Class
 *
 * This is the response from requesting cluster information
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.2
 */
class StatusResponse extends Response {

	/**
	 * Result
	 * @var string $status
	 */
	public string $status;

	/**
	 * Constructor class
	 *
	 * @param object $responseObject Base Object
	 */
	public function __construct(object $responseObject) {
		parent::__construct($responseObject);
	}
}