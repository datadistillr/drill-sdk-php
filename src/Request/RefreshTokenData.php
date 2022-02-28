<?php

namespace datadistillr\Drill\Request;

/**
 * Refresh Token Data POST request object
 *
 * @author Ben Stewart <ben@datadistillr.com>
 * @since 0.6.0
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
	 * RefreshTokenData Constructor
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