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
	 * Refresh Token
	 * @var string $refreshToken
	 */
	public string $refreshToken;

    /**
     * RefreshTokenData Constructor
     *
     * @param string $refreshToken Refresh Token
     */
	public function __construct(string $refreshToken) {
		parent::__construct();

		$this->refreshToken = $refreshToken;
	}
}