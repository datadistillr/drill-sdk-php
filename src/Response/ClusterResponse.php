<?php

namespace datadistillr\Drill\Response;

/**
 * Drill Cluster Information Response Class
 *
 * This is the response from requesting cluster information
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.2
 */
class ClusterResponse extends Response {

	/**
	 * Result
	 * @var array $drillBits
	 */
	public array $drillBits = [];

	/**
	 * Constructor class
	 *
	 * @param array $responseArray Base array
	 */
	public function __construct(array $responseArray) {
		$this->drillBits = $responseArray;

		parent::__construct(null);
	}
}