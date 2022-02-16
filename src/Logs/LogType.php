<?php

namespace datadistillr\Drill\Logs;

/**
 * Log Type Enumeration
 *
 * @package datadistillr\drill-sdk-php
 *
 * @author Tim Swagger <tim@datadistillr.com>
 */
enum LogType: string {
	case Error = 'Error';
	case Warning = 'Warning';
	case Info = 'Info';
	case Request = 'Request';
	case Query = 'Query';
	case Debug = 'Debug';
}