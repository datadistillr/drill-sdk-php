<?php

namespace datadistillr\Drill\Logs;

/**
 * Log Message
 *
 * @package datadistillr\drill-sdk-php
 *
 * @author Tim Swagger <tim@datadistillr.com>
 */
class LogMessage {

	/**
	 * Time Stamp
	 * @var \DateTime $time
	 */
	public \DateTime $time;

	/**
	 * Log Type
	 * @var string $type
	 */
	public string $type;

	/**
	 * Message
	 * @var string $message;
	 */
	public string $message;

	/**
	 * Constructor
	 *
	 * @param LogType $type Log Type
	 * @param string $message Log message
	 */
	public function __construct(LogType $type, string $message) {
		$this->type = $type->value;
		$this->message = $message;
		$this->time = new \DateTime();
	}
}