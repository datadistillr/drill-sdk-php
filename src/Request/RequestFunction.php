<?php

namespace datadistillr\Drill\Request;

/**
 * Request Functions
 *
 * @package datadistillr\drill-sdk-php
 *
 * @author Tim Swagger <tim@datadistillr.com>
 */
enum RequestFunction: string {
	case Query = 'query';
	case Profiles = 'profiles';
	case Profile = 'profile';
	case CancelProfile = 'cancelProfile';
	case Storage = 'storage';
	case CreatePlugin = 'createPlugin';
	case PluginInfo = 'pluginInfo';
	case DeletePlugin = 'deletePlugin';
	case EnablePlugin = 'enablePlugin';
	case DisablePlugin = 'disablePlugin';
	case Drillbits =  'drillbits';
	case Status = 'status';
	case Metrics = 'metrics';
	case ThreadStatus = 'threadStatus';
	case Options = 'options';
}