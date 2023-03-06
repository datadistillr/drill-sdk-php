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
	 * Expires In
	 * @var int $expiresIn
	 */
	public int $expiresIn;

    /**
     * OAuthTokenData Constructor
     *
     * @param string $accessToken Access Token
     * @param string $refreshToken Refresh Token
     * @param int $expiresIn Expires In
     */
	public function __construct(string $accessToken, string $refreshToken, int $expiresIn) {
		parent::__construct();

		$this->accessToken = $accessToken;
		$this->refreshToken = $refreshToken;
		$this->expiresIn = $expiresIn;
	}
}