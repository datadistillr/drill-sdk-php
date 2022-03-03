<?php

namespace datadistillr\Drill\Response;

/**
 * Drill Storage Plugin Response Class
 *
 * This is the response for a Drill Storage Plugin request
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.2
 */
class StoragePluginResponse extends Response {

	/**
	 * Plugin Name
	 * @var string $name
	 */
	public string $name;

	/**
	 * Config Object
	 * @var ?object $config
	 */
	public ?object $config;

	/**
	 * Constructor class
	 *
	 * @param object $responseObject Base Object
	 */
	public function __construct(object $responseObject) {
		parent::__construct($responseObject);
	}

}