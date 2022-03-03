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
     * @param string $accessToken Access Token
     * @param string $refreshToken Refresh Token
     */
	public function __construct(string $accessToken, string $refreshToken) {
		parent::__construct();

		$this->accessToken = $accessToken;
		$this->refreshToken = $refreshToken;
	}
}