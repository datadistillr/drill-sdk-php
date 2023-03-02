<?php

namespace datadistillr\Drill\Request;

/**
 * Request URL
 *
 * This class holds the URL and the request type
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.2
 */
class RequestUrl {

	/**
	 * Request URL
	 * @var string $url
	 */
	protected string $url;

	/**
	 * Function
	 * @var RequestFunction $function
	 */
	protected RequestFunction $function;

	/**
	 * Request Type
	 * @var RequestType $requestType
	 */
	protected RequestType $requestType = RequestType::GET;

	/**
	 * Constructor class
	 *
	 * @param RequestFunction $function The Function to be called
	 * @param string $hostname Drill Server Hostname
	 * @param int $port Port Number [default: 8047]
	 * @param bool $secure Make call via SSL/TLS [default: true]
	 * @param ?string ...$items Any extra to be included [default: null]
	 */
	public function __construct(RequestFunction $function, string $hostname, int $port = 8047, bool $secure = true, ?string ...$items) {

		$this->function = $function;

		$protocol = $secure ? 'https://' : 'http://';

		switch ($function) {
			case RequestFunction::MapQuery:
			case RequestFunction::Query:
				$path = '/query.json';
				$this->requestType = RequestType::POST;
				break;
			case RequestFunction::Profiles:
				$path = '/profiles.json';
				break;
			case RequestFunction::Profile:
				$path = '/profiles/'.$items[0].'.json';
				break;
			case RequestFunction::CancelProfile:
				$path = '/profiles/cancel/'.$items[0];
				break;
			case RequestFunction::Storage:
				$path = '/storage.json';
				break;
			case RequestFunction::CreatePlugin:
				$this->requestType = RequestType::POST;
			case RequestFunction::PluginInfo:
				$path = '/storage/' . $items[0] . '.json';
				break;
			case RequestFunction::DeletePlugin:
				$this->requestType = RequestType::DELETE;
				$path = '/storage/' . $items[0] . '.json';
				break;
			case RequestFunction::EnablePlugin:
				$path = '/storage/' . $items[0] . '/enable/true';
				break;
			case RequestFunction::DisablePlugin:
				$path = '/storage/' . $items[0] . '/enable/false';
				break;
			case RequestFunction::UpdateRefreshToken:
				$path = '/credentials/' . $items[0] . '/update_refresh_token';
                $this->requestType = RequestType::POST;
				break;
			case RequestFunction::UpdateAccessToken:
                $this->requestType = RequestType::POST;
				$path = '/credentials/' . $items[0] . '/update_access_token';
				break;
			case RequestFunction::UpdateOAuthTokens:
                $this->requestType = RequestType::POST;
				$path = '/credentials/' . $items[0] . '/update_oauth_tokens';
				break;
			case RequestFunction::Drillbits:
				$path = '/cluster.json';
				break;
			case RequestFunction::Status:
				$path = '/status';
				break;
			case RequestFunction::Metrics:
				$path = '/status/metrics';
				break;
			case RequestFunction::ThreadStatus:
				$path = '/status/threads';
				break;
			case RequestFunction::Options:
				$path = '/options.json';
				break;
			default:
				$path = '';
		}

		$this->url = $protocol . $hostname . ':' . $port . $path;
	}

	/**
	 * Get Function Value
	 *
	 * @return RequestFunction Function value
	 */
	public function getFunction(): RequestFunction {
		return $this->function;
	}

	/**
	 * get URL
	 *
	 * @return string Fully qualified URL
	 */
	public function getUrl(): string {
		return $this->url;
	}

	/**
	 * Get Request Type
	 *
	 * @return RequestType Request Type
	 */
	public function getRequestType(): RequestType {
		return $this->requestType;
	}
}