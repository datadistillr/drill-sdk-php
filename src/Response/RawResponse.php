<?php

namespace datadistillr\Drill\Response;

/**
 * Raw Response Class
 *
 * This is the class for handling all raw query responses
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.2
 */
class RawResponse extends Response {

	/**
	 * Constructor class
	 *
	 * @param object $responseObject Base Object
	 */
	public function __construct(object $responseObject) {
		parent::__construct($responseObject);
	}
}