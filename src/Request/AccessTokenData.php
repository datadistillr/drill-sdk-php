<?php

namespace datadistillr\Drill\Request;

/**
 * Access Token Data POST request object
 *
 * @author Ben Stewart <ben@datadistillr.com>
 * @since 0.6.0
 */
class AccessTokenData extends RequestData {

	/**
	 * Plugin Name
	 * @var string $name
	 */
	public string $name;

	/**
	 * Access Token
	 * @var string $accessToken
	 */
	public string $accessToken;

	/**
	 * QueryData Constructor
	 *
	 * @param string $pluginName Plugin Name
	 * @param string $accessToken Access Token
	 */
	public function __construct(string $pluginName, string $accessToken) {
		parent::__construct();

		$this->name = $pluginName;
		$this->accessToken = $accessToken;
	}
}