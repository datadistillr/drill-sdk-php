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
	 * @var string $function
	 */
	protected string $function;

	/**
	 * Request Type
	 * @var string $requestType
	 */
	protected string $requestType = 'GET';

	/**
	 * Constructor class
	 *
	 * @param string $function The Function to be called
	 * @param string $hostname Drill Server Hostname
	 * @param int $port Port Number [default: 8047]
	 * @param bool $secure Make call via SSL/TLS [default: true]
	 * @param ?string ...$items Any extra to be included [default: null]
	 */
	public function __construct(string $function, string $hostname, int $port = 8047, bool $secure = true, ?string ...$items) {

		$this->function = $function;

		$protocol = $secure ? 'https://' : 'http://';

		switch ($function) {
			case 'query':
				$path = '/query.json';
				$this->requestType = 'POST';
				break;
			case 'profiles':
				$path = '/profiles.json';
				break;
			case 'profile':
				$path = '/profiles/'.$items[0].'.json';
				break;
			case 'cancelProfile':
				$path = '/profiles/cancel/'.$items[0];
				break;
			case 'storage':
				$path = '/storage.json';
				break;
			case 'createPlugin':
				$this->requestType = 'POST';
			case 'pluginInfo':
				$path = '/storage/' . $items[0] . '.json';
				break;
			case 'deletePlugin':
				$this->requestType = 'DELETE';
				$path = '/storage/' . $items[0] . '.json';
				break;
			case 'enablePlugin':
				$path = '/storage/' . $items[0] . '/enable/true';
				break;
			case 'disablePlugin':
				$path = '/storage/' . $items[0] . '/enable/false';
				break;
			case 'drillbits':
				$path = '/cluster.json';
				break;
			case 'status':
				$path = '/status';
				break;
			case 'metrics':
				$path = '/status/metrics';
				break;
			case 'threadStatus':
				$path = '/status/threads';
				break;
			case 'options':
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
	 * @return string Function value
	 */
	public function getFunction(): string {
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
	 * @return string Request Type
	 */
	public function getRequestType(): string {
		return $this->requestType;
	}
}