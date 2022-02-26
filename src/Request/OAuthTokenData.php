<?php

namespace datadistillr\Drill\Request;

/**
 * OAuth Token Data POST request object
 *
 * @author Ben Stewart <ben@datadistillr.com>
 * @since 0.6.0
 */
class OAuthTokenData extends RequestData {

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
	 * Access Token
	 * @var string $refreshToken
	 */
	public string $refreshToken;

    /**
     * OAuthTokenData Constructor
     *
     * @param string $pluginName Plugin Name
     * @param string $accessToken Access Token
     * @param string $refreshToken Refresh Token
     */
	public function __construct(string $pluginName, string $accessToken, string $refreshToken) {
		parent::__construct();

		$this->name = $pluginName;
		$this->accessToken = $accessToken;
		$this->refreshToken = $refreshToken;
	}
}