<?php

namespace datadistillr\Drill\Response;

/**
 * Drill Confirmation Response Class
 *
 * This is the response from canceling a profile, and other status updates
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.2
 */
class ConfirmationResponse extends Response {

	/**
	 * Result
	 * @var string $result
	 */
	public string $result;

	/**
	 * Constructor class
	 *
	 * @param object $responseObject Base Object
	 */
	public function __construct(object $responseObject) {
		parent::__construct($responseObject);
	}
}