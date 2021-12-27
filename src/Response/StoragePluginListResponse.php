<?php

namespace datadistillr\Drill\Response;

/**
 * Drill Storage Plugin List Response Class
 *
 * This is the response for a Drill Storage Plugin request
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.2
 */
class StoragePluginListResponse extends Response {

	/**
	 * Plugins
	 * @var array $plugins
	 */
	public array $plugins = [];

	/**
	 * Constructor class
	 *
	 * @param array $responseArray Base array
	 */
	public function __construct(array $responseArray) {
		foreach($responseArray as $item) {
			$this->plugins[] = new StoragePluginResponse($item);
		}

		parent::__construct(null);
	}
}