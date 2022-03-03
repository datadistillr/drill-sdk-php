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
	 * Access Token
	 * @var string $accessToken
	 */
	public string $accessToken;

	/**
	 * AccessTokenData Constructor
	 *
	 * @param string $accessToken Access Token
	 */
	public function __construct(string $accessToken) {
		parent::__construct();

		$this->accessToken = $accessToken;
	}
}