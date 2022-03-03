<?php

namespace datadistillr\Drill\Response;

/**
 * Drill Options Response Class
 *
 * This is the response for a Drill Options request
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.2
 */
class OptionsListResponse extends Response {

	/**
	 * Options
	 * @var array $options
	 */
	public array $options = [];

	/**
	 * Constructor class
	 *
	 * @param array $responseArray Base array
	 */
	public function __construct(array $responseArray) {
		foreach($responseArray as $item) {
			$this->options[] = new StoragePluginResponse($item);
		}

		parent::__construct(null);
	}
}