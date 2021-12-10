<?php

namespace thedataist\Drill\ResultSet;

/**
 * Abstract Class ResultSet
 * @package thedataist\Drill
 *
 * @author Tim Swagger <tim@datadistillr.com>
 * @since 0.6.0
 */
abstract class ResultSet {

	// region Properties

	// endregion

	/**
	 * ResultSet constructor.
	 */
	public function __construct() {

	}

	/**
	 * Magic method setter
	 *
	 * @param string $name Property name
	 * @param mixed $value property value
	 */
	public function __set(string $name, $value): void {
		$this->$name = $value;
	}

	/**
	 * Magic method getter
	 *
	 * @param string $name Property Name
	 * @return mixed Property Value
	 */
	public function __get(string $name) {
		return $this->$name;
	}
}