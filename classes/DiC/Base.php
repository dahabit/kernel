<?php

namespace Fuel\Kernel\DiC;

class Base implements Dependable
{
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
		$classlower = strtolower($classname);
		if (isset($this->classes[$classlower]))
		{
			return $this->classes[$classlower];
		}

		return $this->parent ? $this->parent->get_class($classname) : $classname;
	}

	/**
	 * Forges a new object for the given class
	 *
	 * @param   string  $classname
	 * @return  object
	 */
	public function forge($classname)
	{
		$classname = $this->get_class($classname);
		if ( ! class_exists($classname))
		{
			throw new \RuntimeException('Class "'.$classname.'" not found.');
		}

		$args        = array_slice(func_get_args(), 1);
		$reflection  = new \ReflectionClass($classname);
		$instance    = $args ? $reflection->newInstanceArgs($args) : $reflection->newInstance();

		// Setter support for the instance to know which app created it
		if ($reflection->hasMethod('_set_app'))
		{
			$instance->_set_app($this->app);
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
		$classname = strtolower($classname);
		$name = is_null($name) ? '__default' : strtolower($name);
		if ( ! isset($this->objects[$classname][$name]))
		{
			if ( ! $this->parent or $name == '__default')
			{
				// Return 'default' instance when no name is given, is forged without params
				if ($name == '__default')
				{
					$default = $this->forge($classname);
					$this->set_object($classname, $name, $default);
					return $this->objects[$classname][$name];
				}

				throw new \RuntimeException('Instance "'.$name.'" not found for class "'.$classname.'".');
			}
			return $this->parent->get_object($classname, $name);
		}
		return $this->objects[$classname][$name];
	}
}
