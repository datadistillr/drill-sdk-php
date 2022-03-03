<?php

namespace datadistillr\Drill\Response;

/**
 * Abstract Drill Response Class
 *
 * This is the base class for handling all drill responses
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.2
 */
abstract class Response {

	/**
	 * Constructor class
	 *
	 * @param ?object $responseObject Base Object
	 */
	public function __construct(?object $responseObject) {
		if(isset($responseObject)) {
			foreach ($responseObject as $key => $value) {
				$this->$key = $value;
			}
		}
	}
}