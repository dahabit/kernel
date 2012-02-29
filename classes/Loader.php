<?php

namespace Fuel\Kernel;
use Fuel\Kernel\Environment;

class Loader
{
	/**
	 * @var  int  Keyname for Application packages
	 */
	const TYPE_APP = 0;

	/**
	 * @var  int  Keyname for normal packages
	 */
	const TYPE_PACKAGE = 1000;

	/**
	 * @var  int  Keyname for "Core" packages (non routable, always last)
	 */
	const TYPE_CORE = 100000;

	/**
	 * @var  array  active loaders in a prioritized list
	 */
	protected $packages = array(
		Loader::TYPE_APP      => array(),
		Loader::TYPE_PACKAGE  => array(),
		Loader::TYPE_CORE     => array(),
	);

	/**
	 * @var  array  namespaces that may be aliased to global (for Fuel v1 BC)
	 */
	protected $global_ns_aliases = array();

	/**
	 * @var  string  classname of the class currently being loaded
	 */
	protected $__current_class_load = '';

	/**
	 * Adds a package
	 *
	 * @param   string|Loader\Loadable  $name
	 * @param   int                     $type
	 * @return  Loader\Loadable         for method chaining
	 * @throws  \RuntimeException
	 */
	public function load_package($name, $type = Loader::TYPE_PACKAGE)
	{
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

		$this->packages[$type][$name] = $loader;
		return $loader;
	}

	/**
	 * Fetch a specific package
	 *
	 * @param   string  $name
	 * @param   int     $type
	 * @return  Loader\Loadable
	 * @throws  \OutOfBoundsException
	 */
	public function package($name, $type = Loader::TYPE_PACKAGE)
	{
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
	 */
	public static function load_app($appname, \Closure $config)
	{
		$loader = _loader()->load_package($appname, Loader::TYPE_APP);
		$loader->set_routable(true);

		$class = _env()->app_class($appname);
		return new $class($config, $loader);
	}

	/**
	 * Attempts to load a class from a package
	 *
	 * @param   string  $class
	 * @return  bool
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
	 */
	public function add_global_ns_alias($ns)
	{
		$this->global_ns_aliases[] = trim($ns, '\\').'\\';
		return $this;
	}
}
