<?php

namespace Fuel\Kernel\Data;
use Fuel\Kernel\Application;

abstract class Base
{
	/**
	 * @var  array  keeps the data
	 */
	protected $_data = array();

	/**
	 * @var  array  descendants of this class
	 */
	protected $_children = array();

	/**
	 * @var  \Fuel\Kernel\Application\Base
	 */
	protected $_app;

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param  \Fuel\Kernel\Application\Base  $app
	 */
	public function _set_app(Application\Base $app)
	{
		$this->_app = $app;
	}

	/**
	 * Gets a dot-notated key from data, with a default value if it does not exist.
	 *
	 * @param   mixed   $key      The dot-notated key or array of keys
	 * @param   string  $default  The default value
	 * @return  mixed
	 */
	public function get($key, $default = null)
	{
		// Return full data array on null input
		if (is_null($key))
		{
			return $this->_data;
		}

		// Fetch multiple keys
		if (is_array($key))
		{
			$return = array();
			foreach ($key as $k)
			{
				$return[$k] = $this->get($k, $default);
			}
			return $return;
		}

		return array_get_dot_key($key, $this->_data, $return) ? $return : $default;
	}

	/**
	 * Set a data item (dot-notated) to the value.
	 *
	 * @param   mixed   $key    The dot-notated key to set or array of keys
	 * @param   mixed   $value  The value
	 * @return  Base    for method chaining
	 */
	public function set($key, $value = null)
	{
		if (is_null($value) and is_array($key))
		{
			foreach ($key as $k => $v)
			{
				$this->set($k, $v);
			}
			return $this;
		}

		array_set_dot_key($key, $this->_data, $value);
		return $this;
	}
}
