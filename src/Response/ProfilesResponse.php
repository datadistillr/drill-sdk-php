<?php

namespace datadistillr\Drill\Response;

/**
 * Drill Profiles List Response Class
 *
 * This is the response for a Drill Profiles request
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.2
 */
class ProfilesResponse extends Response {

	/**
	 * Running Queries
	 * @var array $runningQueries
	 */
	public array $runningQueries = [];

	/**
	 * Finished Queries
	 * @var array $finishedQueries
	 */
	public array $finishedQueries = [];

	/**
	 * Constructor class
	 *
	 * @param object $responseObject Base Object
	 */
	public function __construct(object $responseObject) {
		parent::__construct($responseObject);
	}
}