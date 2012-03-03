<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\DiC;
use Fuel\Kernel\Environment;

/**
 * Dependency Injection Container Base class
 *
 * - keeps a list of classnames and the actual class you want for that
 * - keeps a container of objects that are retrievable
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
class Base implements Dependable
{
	/**
	 * @var  string  name for default instance in object container
	 *
	 * @since  2.0.0
	 */
	const DEFAULT_NAME = '__default';

	/**
	 * @var  array  classnames and their 'translation'
	 *
	 * @since  2.0.0
	 */
	protected $classes = array();

	/**
	 * @var  array  named instances organized by classname
	 *
	 * @since  2.0.0
	 */
	protected $objects = array();

	/**
	 * @var  \Fuel\Kernel\Environment
	 *
	 * @since  2.0.0
	 */
	protected $env;

	/**
	 * @var  \Fuel\Kernel\Application\Base
	 *
	 * @since  2.0.0
	 */
	protected $app;

	/**
	 * @var  Dependable  parent DiC to fall back on
	 *
	 * @since  2.0.0
	 */
	protected $parent;

	/**
	 * Constructor
	 *
	 * @param  \Fuel\Kernel\Environment            $env
	 * @param  null|\Fuel\Kernel\Application\Base  $app
	 * @param  null|Base  $parent  fallback if a call to this DiC fails
	 *
	 * @since  2.0.0
	 */
	public function __construct(Environment $env, $app = null, $parent = null)
	{
		$this->env = $env;
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
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
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

		// Setter support for the instance to take the current environment
		if ($reflection->hasMethod('_set_env'))
		{
			$instance->_set_env($this->env);
		}
		// Setter support for the instance to take the app that created it
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
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
	 */
	public function get_object($classname, $name = null)
	{
		$class = strtolower($classname);
		$default = is_null($name);

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
			if ($default)
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
