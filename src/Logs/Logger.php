<?php

namespace datadistillr\Drill\Logs;

/**
 * Logger Trait
 * Simple means to log message.
 *
 * @package datadistillr\drill-sdk-php
 *
 * @author Tim Swagger <tim@datadistillr.com>
 */
trait Logger {

	/**
	 * Log messages
	 * @var LogMessage[] $log
	 */
	protected array $log = [];


	/**
	 * Log a message
	 *
	 * @param LogType $type Log Type
	 * @param string $message Log Message
	 * @return void
	 */
	public function logMessage(LogType $type, string $message): void {

		$this->log[] = new LogMessage($type, $message);
	}

	/**
	 * Get Logs Array
	 *
	 * @return LogMessage[] Log Message Array
	 */
	public function getLogs(): array {
		return $this->log;
	}

	/**
	 * Get Logs Stringified Array
	 *
	 * @return LogMessage[] Log Message Array
	 */
	public function getLogsStringify(): array {
		$stringifiedLog = [];

		foreach($this->log as $log) {
			$item = $log->type . ' - ';
			$item .= $log->time->format('Y-m-d H:i:s v') . ' --> ';
			$item .= $log->message;

			$stringifiedLog[] = $item;
		}
		return $stringifiedLog;
	}
}