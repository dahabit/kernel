<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel;
use Fuel\Kernel\Environment;

/**
 * Loader container
 *
 * This acts as the Autoloader added to PHP and handles Package loading.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
class Loader
{
	/**
	 * @var  int  Keyname for Application packages
	 *
	 * @since  2.0.0
	 */
	const TYPE_APP = 0;

	/**
	 * @var  int  Keyname for normal packages
	 *
	 * @since  2.0.0
	 */
	const TYPE_PACKAGE = 1000;

	/**
	 * @var  int  Keyname for "Core" packages (non routable, always last)
	 *
	 * @since  2.0.0
	 */
	const TYPE_CORE = 100000;

	/**
	 * @var  \Fuel\Kernel\Environment
	 *
	 * @since  2.0.0
	 */
	protected $env;

	/**
	 * @var  array  active loaders in a prioritized list
	 *
	 * @since  2.0.0
	 */
	protected $packages = array(
		Loader::TYPE_APP      => array(),
		Loader::TYPE_PACKAGE  => array(),
		Loader::TYPE_CORE     => array(),
	);

	/**
	 * @var  array  namespaces that may be aliased to global (for Fuel v1 BC)
	 *
	 * @since  2.0.0
	 */
	protected $global_ns_aliases = array();

	/**
	 * @var  string  classname of the class currently being loaded
	 *
	 * @since  2.0.0
	 */
	protected $__current_class_load = '';

	/**
	 * Magic Fuel method that is the setter for the current Environment
	 *
	 * @param   \Fuel\Kernel\Environment  $env
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function _set_env(Environment $env)
	{
		$this->env = $env;
	}

	/**
	 * Adds a package
	 *
	 * @param   string|Loader\Loadable  $name
	 * @param   int                     $type
	 * @return  Loader\Loadable         for method chaining
	 * @throws  \RuntimeException
	 *
	 * @since  2.0.0
	 */
	public function load_package($name, $type = Loader::TYPE_PACKAGE)
	{
		// Return directly when already loaded
		try
		{
			return $this->package($name, $type);
		}
		catch (\Exception $e) {}

		// Directly add an unnamed package
		if ($name instanceof Loader\Loadable)
		{
			$loader = $name;
			$name = uniqid();
		}
		// Directly add a named package: array($name, $loader)
		elseif (is_array($name) and end($name) instanceof Loader\Loadable)
		{
			$loader = end($name);
			$name = reset($name);
		}
		// Add a package using a name or using: array($name, $fullpath)
		else
		{
			! is_array($name) and $name = array($name, Environment::instance()->path('fuel').$name.'/');
			list($name, $path) = $name;

			// Check if the package hasn't already been loaded
			if (isset($this->packages[$type][$name]))
			{
				throw new \RuntimeException('Package already loaded, can\'t be loaded twice.');
			}

			// Set variable necessary for the package loader object
			$env = $this->env;

			// Fetch the Package loader
			$loader = require $path.'loader.php';
			if ( ! $loader instanceof Loader\Loadable)
			{
				throw new \RuntimeException('Package loader must implement Fuel\\Kernel\\Loader\\Base');
			}
		}

		// If it's an app, include the Application class
		if ($type == static::TYPE_APP)
		{
			require_once $loader->path().'application.php';
		}

		$this->packages[$type][$name] = $loader->set_name($name);
		return $loader;
	}

	/**
	 * Fetch a specific package
	 *
	 * @param   string  $name
	 * @param   int     $type
	 * @return  Loader\Loadable
	 * @throws  \OutOfBoundsException
	 *
	 * @since  2.0.0
	 */
	public function package($name, $type = Loader::TYPE_PACKAGE)
	{
		// Ensure the name is a string
		if ( ! is_string($name))
		{
			$name = is_array($name) ? reset($name) : $name->name;
		}

		// Check if it exists
		if ( ! isset($this->packages[$type][$name]))
		{
			throw new \OutOfBoundsException('Unknown package: '.$name);
		}

		return $this->packages[$type][$name];
	}

	/**
	 * Fetch all packages or just those of a specific type
	 *
	 * @param   int|null  $type  null for all, int for a specific type
	 * @return  array
	 * @throws  \OutOfBoundsException
	 *
	 * @since  2.0.0
	 */
	public function packages($type = null)
	{
		if (is_null($type))
		{
			return $this->packages;
		}
		elseif ( ! isset($this->packages[$type]))
		{
			throw new \OutOfBoundsException('Unknown package type: '.$type);
		}

		return $this->packages[$type];
	}

	/**
	 * Load application and return instantiated
	 *
	 * @param   string   $appname
	 * @param   Closure  $config
	 * @return  \Fuel\Kernel\Application\Base
	 * @throws  \OutOfBoundsException
	 *
	 * @since  2.0.0
	 */
	public function load_application($appname, \Closure $config)
	{
		$loader = $this->load_package($appname, Loader::TYPE_APP);
		$loader->set_routable(true);

		$class = $this->env->application_class($appname);
		$app = new $class($this->env, $config, $loader);

		return $app;
	}

	/**
	 * Attempts to load a class from a package
	 *
	 * @param   string  $class
	 * @return  bool
	 *
	 * @since  2.0.0
	 */
	public function load_class($class)
	{
		$class = ltrim($class, '\\');

		if (empty($this->__current_class_load))
		{
			$this->__current_class_load = $class;
		}

		try
		{
			foreach ($this->packages as $pkgs)
			{
				foreach ($pkgs as $pkg)
				{
					if ($pkg->load_class($class))
					{
						$this->init_class($class);
						return true;
					}
				}
			}
		}
		catch (\Exception $e)
		{
			$this->__current_class_load = null;
			throw $e;
		}

		/**
		 * Support for Fuel v1 style classes
		 */
		foreach ($this->global_ns_aliases as $ns_alias)
		{
			if (strpos($class, $ns_alias) !== 0 and $this->load_class($ns_alias.$class))
			{
				class_alias($ns_alias.$class, $class);
				$this->init_class($class);
				return true;
			}
		}

		if ($this->__current_class_load == $class)
		{
			$this->__current_class_load = null;
		}

		return false;
	}

	/**
	 * Initializes a class when it's the requested one and has a static _init() method
	 *
	 * @param   string  $class
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	protected function init_class($class)
	{
		if ($this->__current_class_load == $class)
		{
			$this->__current_class_load == null;
			if (method_exists($class, '_init'))
			{
				call_user_func($class.'::_init');
			}
		}
	}

	/**
	 * Add a global namespace alias
	 *
	 * @param   string  $ns
	 * @return  Loader  for method chaining
	 *
	 * @since  2.0.0
	 */
	public function add_global_ns_alias($ns)
	{
		$this->global_ns_aliases[] = trim($ns, '\\').'\\';
		return $this;
	}
}
