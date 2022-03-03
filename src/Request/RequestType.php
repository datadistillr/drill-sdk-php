<?php

namespace datadistillr\Drill\Request;

/**
 * Supported HTTP Request Types
 *
 * @package datadistillr\drill-sdk-php
 *
 * @author Tim Swagger <tim@datadistillr.com>
 */
enum RequestType: string {
	case GET = 'GET';
	case POST = 'POST';
	case DELETE = 'DELETE';
}