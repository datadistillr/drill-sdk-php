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
class ProfileResponse extends Response {

	/**
	 * ID
	 * @var object $id
	 */
	public object $id;

	/**
	 * Type
	 * @var int $type
	 */
	public int $type;

	/**
	 * Start Time
	 * @var int $start
	 */
	public int $start;

	/**
	 * End Time
	 * @var int $end
	 */
	public int $end;

	/**
	 * Query String
	 * @var string $query
	 */
	public string $query;

	/**
	 * Plan
	 * @var string $plan
	 */
	public string $plan;

	// NOTE: unsure (at the moment) of other properties that need to go here.

//	/**
//	 * Last Update time
//	 * @var int $lastUpdate
//	 */
//	public int $lastUpdate;
//
//	/**
//	 * Last Progress time
//	 * @var int $lastProgress
//	 */
//	public int $lastProgress;

	/**
	 * User
	 * @var string $user
	 */
	public string $user;

	/**
	 * Constructor class
	 *
	 * @param object $responseObject Base Object
	 */
	public function __construct(object $responseObject) {
		parent::__construct($responseObject);
	}
}