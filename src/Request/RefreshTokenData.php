<?php

namespace datadistillr\Drill\Request;

/**
 * Refresh Token Data POST request object
 *
 * @author Ben Stewart <ben@datadistillr.com>
 * @since 0.7.1
 */
class RefreshTokenData extends RequestData {

	/**
	 * Plugin Name
	 * @var string $name
	 */
	public string $name;

	/**
	 * Refresh Token
	 * @var string $refreshToken
	 */
	public string $refreshToken;

	/**
	 * QueryData Constructor
	 *
	 * @param string $pluginName Plugin Name
	 * @param string $refreshToken Refresh Token
	 */
	public function __construct(string $pluginName, string $refreshToken) {
		parent::__construct();

		$this->name = $pluginName;
		$this->refreshToken = $refreshToken;
	}
}