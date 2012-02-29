<?php

namespace Fuel\Kernel\DiC;

class Base implements Dependable
{
	/**
	 * @var  string  name for default instance in object container
	 */
	const DEFAULT_NAME = '__default';

	/**
	 * @var  array  classnames and their 'translation'
	 */
	protected $classes = array();

	/**
	 * @var  array  named instances organized by classname
	 */
	protected $objects = array();

	/**
	 * @var  \Fuel\Kernel\Application\Base
	 */
	protected $app;

	/**
	 * @var  Dependable  parent DiC to fall back on
	 */
	protected $parent;

	public function __construct($app = null, $parent = null)
	{
		$this->app = $app;
		if ($parent instanceof Dependable)
		{
			$this->parent = $parent;
		}
	}

	/**
	 * Set class that is fetched from the classes property
	 *
	 * @param   string     $classname
	 * @param   string     $actual
	 * @return  Dependable  to allow method chaining
	 */
	public function set_class($classname, $actual)
	{
		$this->set_classes(array($classname => $actual));
		return $this;
	}

	/**
	 * Set classes that are fetched from the classes property
	 *
	 * @param   array      $classnames
	 * @return  Dependable  to allow method chaining
	 */
	public function set_classes(array $classnames)
	{
		foreach ($classnames as $classname => $actual)
		{
			$this->classes[strtolower($classname)] = $actual;
		}
		return $this;
	}

	/**
	 * Translates a classname to the one set in the classes property
	 *
	 * @param   string  $classname
	 * @return  string
	 */
	public function get_class($classname)
	{
		// First check if classname is available as-is
		$classlower = strtolower($classname);
		if (isset($this->classes[$classlower]))
		{
			return $this->classes[$classlower];
		}

		// Fall back on the environment if not found here
		$translated = $this->parent ? $this->parent->get_class($classname) : $classname;

		// When returned unchanged and includes a colon, retry without the colon
		if ($pos = strrpos($translated, ':'))
		{
			return $this->get_class(substr($classname, 0, $pos));
		}

		// Nothing matched, return unchanged
		return $translated;
	}

	/**
	 * Forges a new object for the given class
	 *
	 * @param   string|array  $classname  classname or array($obj_name, $classname)
	 * @return  object
	 */
	public function forge($classname)
	{
		// Detect if a name was given for the DiC
		$name = null;
		is_array($classname) and list($name, $classname) = $classname;

		$class = $this->get_class($classname);
		if ( ! class_exists($class))
		{
			throw new \RuntimeException('Class "'.$class.'" not found.');
		}

		$args        = array_slice(func_get_args(), 1);
		$reflection  = new \ReflectionClass($class);
		$instance    = $args ? $reflection->newInstanceArgs($args) : $reflection->newInstance();

		// Setter support for the instance to know which app created it
		if ($reflection->hasMethod('_set_app'))
		{
			$instance->_set_app($this->app);
		}

		if ( ! is_null($name))
		{
			$this->set_object($classname, $name, $instance);
		}

		return $instance;
	}

	/**
	 * Register an instance with the DiC
	 *
	 * @param   string  $classname
	 * @param   string  $name
	 * @param   object  $instance
	 * @return  Dependable
	 */
	public function set_object($classname, $name, $instance)
	{
		$this->objects[strtolower($classname)][strtolower($name)] = $instance;
		return $this;
	}

	/**
	 * Fetch an instance from the DiC
	 *
	 * @param   string  $classname
	 * @param   string  $name
	 * @return  object
	 * @throws  \RuntimeException
	 */
	public function get_object($classname, $name = null)
	{
		$class = strtolower($classname);

		// When colon found, shorten to classname without colon
		// and default name to everything after the first colon
		if ($pos = strpos($classname, ':'))
		{
			$class = strtolower(substr($classname, 0, $pos));
			is_null($name) and $name = substr($classname, $pos + 1).$name;
		}
		// When name is still null set it to the default, otherwise lowercase the name
		$name = is_null($name) ? self::DEFAULT_NAME : strtolower($name);

		if ( ! isset($this->objects[$class][$name]))
		{
			// Return 'default' instance when no name is given, is forged without params
			if ($name == self::DEFAULT_NAME)
			{
				$default = $this->forge($classname);
				$this->set_object($class, $name, $default);
				return $this->objects[$class][$name];
			}
			// Throw exception when given name is not found
			elseif ( ! $this->parent)
			{
				throw new \RuntimeException('Instance "'.$name.'" not found for class "'.ucfirst($class).'".');
			}
			// Or attempt parent when possible
			return $this->parent->get_object($class, $name);
		}

		// Object was found, return it
		return $this->objects[$class][$name];
	}
}
