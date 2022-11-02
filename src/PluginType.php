<?php

namespace datadistillr\Drill;

/**
 * Plugin Type Enumeration
 *
 * @package datadistillr\drill-sdk-php
 *
 * @author Tim Swagger <tim@datadistillr.com>
 */
enum PluginType: string {
	case File = 'file';
	case GoogleSheets = 'googlesheets';
	case JDBC = 'jdbc';
	case Mongo = 'mongo';
	case Elastic = 'elastic';
	case Splunk = 'splunk';
}