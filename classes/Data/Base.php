<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Data;
use Fuel\Kernel\Application;

/**
 * Data Base class
 *
 * Abstract container for classes like Language and Config.
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
abstract class Base
{
	/**
	 * @var  array  keeps the data
	 *
	 * @since  2.0.0
	 */
	protected $_data = array();

	/**
	 * @var  array  descendants of this class
	 *
	 * @since  2.0.0
	 */
	protected $_children = array();

	/**
	 * @var  \Fuel\Kernel\Application\Base
	 *
	 * @since  2.0.0
	 */
	protected $_app;

	/**
	 * Constructor
	 *
	 * @param  array  $data
	 * @param  null   $name
	 * @param  null   $parent
	 *
	 * @since  2.0.0
	 */
	public function __construct(array $data = array(), $name = null, $parent = null)
	{
		$this->_data = $data;
		if (is_string($name) and $parent instanceof self)
		{
			$parent->add_child($name, $this);
		}
	}

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param   \Fuel\Kernel\Application\Base  $app
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function _set_app(Application\Base $app)
	{
		$this->_app = $app;
	}

	/**
	 * Add a named child Data object
	 *
	 * @param   string  $name
	 * @param   Base    $child
	 * @return  Base
	 *
	 * @since  2.0.0
	 */
	public function add_child($name, self $child)
	{
		$this->_children[$name] = $child;
		return $this;
	}

	/**
	 * Gets a dot-notated key from data, with a default value if it does not exist.
	 *
	 * @param   mixed   $key      The dot-notated key or array of keys
	 * @param   string  $default  The default value
	 * @return  mixed
	 *
	 * @since  2.0.0
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
			return __val($return);
		}

		// When found, return
		if (array_get_dot_key($key, $this->_data, $return))
		{
			return __val($return);
		}
		// Attempt to find child that matches first segment and if so attempt there
		elseif (($pos = strpos($key, '.')) and isset($this->_children[$name = substr($key, 0, $pos)]))
		{
			return __val($this->_children[$name]->get(substr($key, $pos + 1), $default));
		}

		// Failure
		return __val($default);
	}

	/**
	 * Set a data item (dot-notated) to the value.
	 *
	 * @param   mixed   $key    The dot-notated key to set or array of keys
	 * @param   mixed   $value  The value
	 * @return  Base    for method chaining
	 *
	 * @since  2.0.0
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

		array_set_dot_key($key, $this->_data, __val($value));
		return $this;
	}

	/**
	 * PHP magic method, gets a simple (non dot-notated) value from config
	 *
	 * @param   string  $property
	 * @return  mixed
	 *
	 * @since  2.0.0
	 */
	public function __get($property)
	{
		return $this->get($property);
	}

	/**
	 * PHP magic method, sets a simple (non dot-notated) value in config
	 *
	 * @param   string  $property
	 * @param   mixed   $value
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function __set($property, $value)
	{
		$this->set($property, $value);
	}

	/**
	 * PHP magic method: invoking is the same as using get()
	 *
	 * @param   string  $property
	 * @param   null    $default
	 * @return  mixed
	 */
	public function __invoke($property, $default = null)
	{
		return $this->get($property, $default);
	}
}
