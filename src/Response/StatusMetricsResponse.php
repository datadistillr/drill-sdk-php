<?php

namespace datadistillr\Drill\Response;

/**
 * Drill Status Metrics Response Class
 *
 * This is the response from requesting cluster information
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.2
 */
class StatusMetricsResponse extends Response {

	/**
	 * Version
	 * @var string $version
	 */
	public string $version;

	/**
	 * Gauges
	 * @var object $gauges
	 */
	public object $gauges;

	/**
	 * Meters
	 * @var object $meters
	 */
	public object $meters;

	/**
	 * Timers
	 * @var object $timers
	 */
	public object $timers;

	/**
	 * Constructor class
	 *
	 * @param object $responseObject Base Object
	 */
	public function __construct(object $responseObject) {
		parent::__construct($responseObject);
	}
}