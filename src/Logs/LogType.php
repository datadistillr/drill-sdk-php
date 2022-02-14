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
	case Emergency = 'Emergency';
	case Alert = 'Alert';
	case Critical = 'Critical';
	case Error = 'Error';
	case Warning = 'Warning';
	case Notice = 'Notice';
	case Info = 'Info';
	case Debug = 'Debug';
}