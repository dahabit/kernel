<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Loader;
use Fuel\Kernel\Environment;

/**
 * Package Loader
 *
 * Default Fuel Package loader class that allows loading files & classes.
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
class Package implements Loadable
{
	/**
	 * @var  \Fuel\Kernel\Environment
	 */
	protected $env;

	/**
	 * @var  string  basepath for the package
	 *
	 * @since  2.0.0
	 */
	protected $path = '';

	/**
	 * @var  string  base namespace for the package (with trailing backslash when not empty)
	 *
	 * @since  2.0.0
	 */
	protected $namespace = '';

	/**
	 * @var  string  string to prefix the Controller classname with, will be relative to the base namespace
	 *
	 * @since  2.0.0
	 */
	protected $class_prefixes = array(
		'controller'  => 'Controller\\',
		'model'       => 'Model\\',
		'presenter'   => 'Presenter\\',
		'task'        => 'Task\\',
	);

	/**
	 * @var  array  package modules with array(relative path => relative subnamespace) (with trailing backslash)
	 *
	 * @since  2.0.0
	 */
	protected $modules = array();

	/**
	 * @var  array  registered classes, without the base namespace
	 *
	 * @since  2.0.0
	 */
	protected $classes = array();

	/**
	 * @var  array  classes that are aliased: classname => actual class
	 *
	 * @since  2.0.0
	 */
	protected $class_aliases = array();

	/**
	 * @var  bool|string  whether this package is routable (bool) or routability is triggered by a prefix (string)
	 *
	 * @since  2.0.0
	 */
	protected $routable = false;

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

		// Show package loads inside application
		($app = $env->active_application())
			and $app->notifier->notify('package_loader_created', $this, __METHOD__);
	}

	/**
	 * Returns the base path for this package
	 *
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	public function path()
	{
		return $this->path;
	}

	/**
	 * Attempt to load a class from the package
	 *
	 * @param   string  $class
	 * @return  bool
	 *
	 * @since  2.0.0
	 */
	public function load_class($class)
	{
		// Save the original classname
		$original = $class;

		// Check if the class path was registered with the Package
		if (isset($this->classes[$class]))
		{
			require $this->classes[$class];
			return true;
		}
		// Check if the request class is an alias registered with the Package
		elseif (isset($this->class_aliases[$class]))
		{
			class_alias($this->class_aliases[$class], $class);
			return true;
		}

		// If a base namespace was set and doesn't match the class: fail
		if ($this->namespace and strpos($class, $this->namespace) !== 0)
		{
			return false;
		}

		// Anything further will be relative to the base namespace
		$class = substr($class, strlen($this->namespace));

		// Check if any of the modules' namespaces matches the class and make it relative on such a match
		$path = $this->path;
		foreach ($this->modules as $m_path => $m_namespace)
		{
			if (strpos($class, $m_namespace) === 0)
			{
				$class  = substr($class, strlen($m_namespace));
				$path  .= 'modules/'.$m_path.'/';
				break;
			}
		}
		$path = $this->class_to_path($original, $class, $path.'classes/');

		// When found include the file and return success
		if (is_file($path))
		{
			require $path;
			return true;
		}

		// ... still here? Failure.
		return false;
	}

	/**
	 * Converts a classname to a path using PSR-0 conventions
	 *
	 * NOTE: using the base namespace setting and usage of modules break PSR-0 convention. The paths are expected
	 * relative to the base namespace when used and optionally relative to the module's (sub)namespace.
	 *
	 * @param   string  $fullname  full classname
	 * @param   string  $class     classname relative to base/module namespace
	 * @param   string  $basepath
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	protected function class_to_path($fullname, $class, $basepath)
	{
		$file  = '';
		if ($last_ns_pos = strripos($class, '\\'))
		{
			$namespace = substr($class, 0, $last_ns_pos);
			$class = substr($class, $last_ns_pos + 1);
			$file = str_replace('\\', '/', $namespace).'/';
		}
		$file .= str_replace('_', '/', $class).'.php';

		return $basepath.$file;
	}

	/**
	 * Set a base path for the package
	 *
	 * @param   string  $path
	 * @return  Package
	 *
	 * @since  2.0.0
	 */
	public function set_path($path)
	{
		$this->path = rtrim($path, '/\\').'/';
		return $this;
	}

	/**
	 * Set a base namespace for the package, only classes from that namespace are loaded
	 *
	 * @param   string  $namespace
	 * @return  Package
	 *
	 * @since  2.0.0
	 */
	public function set_namespace($namespace)
	{
		$this->namespace = trim($namespace, '\\').'\\';
		return $this;
	}

	/**
	 * Add a module with path & namespace
	 *
	 * @param   string  $path
	 * @param   string  $namespace
	 * @return  Package
	 *
	 * @since  2.0.0
	 */
	public function add_module($path, $namespace)
	{
		$this->modules[trim($path, '/\\').'/'] = trim($namespace, '\\').'\\';
		return $this;
	}

	/**
	 * Remove a module from the package
	 *
	 * @param   string  $path
	 * @return  Package
	 *
	 * @since  2.0.0
	 */
	public function remove_module($path)
	{
		unset($this->modules[trim($path, '/\\').'/']);
		return $this;
	}

	/**
	 * Adds a class to the Package that doesn't need to be found
	 *
	 * @param   string  $class
	 * @param   string  $path
	 * @return  Package
	 *
	 * @since  2.0.0
	 */
	public function add_class($class, $path)
	{
		return $this->add_classes(array($class => $path));
	}

	/**
	 * Adds classes to the Package that don't need to be found
	 *
	 * @param   array  $classes
	 * @return  Package
	 *
	 * @since  2.0.0
	 */
	public function add_classes(array $classes)
	{
		foreach ($classes as $class => $path)
		{
			$this->classes[$class] = $path;
		}
		return $this;
	}

	/**
	 * Add an alias and the actual classname
	 *
	 * @param   string   $alias
	 * @param   string   $actual
	 * @return  Package  for method chaining
	 *
	 * @since  2.0.0
	 */
	public function add_class_alias($alias, $actual)
	{
		return $this->add_class_aliases(array($alias => $actual));
	}

	/**
	 * Add multiple classes with their aliases
	 *
	 * @param   array    $classes
	 * @return  Package  for method chaining
	 *
	 * @since  2.0.0
	 */
	public function add_class_aliases(array $classes = array())
	{
		foreach ($classes as $alias => $actual)
		{
			$this->class_aliases[$alias] = $actual;
		}
		return $this;
	}

	/**
	 * Removes a class from the package
	 *
	 * @param   string  $class
	 * @return  Package
	 *
	 * @since  2.0.0
	 */
	public function remove_class($class)
	{
		unset($this->classes[$class]);
		return $this;
	}

	/**
	 * Sets routability of this package
	 *
	 * @param   bool  $routable
	 * @return  Package
	 *
	 * @since  2.0.0
	 */
	public function set_routable($routable)
	{
		$this->routable = $routable;
		return $this;
	}

	/**
	 * Change a special class type prefix
	 *
	 * @param   string  $type
	 * @param   string  $prefix
	 * @return  Package
	 *
	 * @since  2.0.0
	 */
	public function set_class_type_prefix($type, $prefix)
	{
		$this->class_prefixes[strtolower($type)] = $prefix;
		return $this;
	}

	/**
	 * Get the class prefix for a specific type
	 *
	 * @param   string  $type
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	public function class_type_prefix($type)
	{
		$type = strtolower($type);
		return isset($this->class_prefixes[$type]) ? $this->class_prefixes[$type] : '';
	}

	/**
	 * Attempts to find a controller, loads the class and returns the classname if found
	 *
	 * @param   string  $type  for example: controller or task
	 * @param   string  $path
	 * @return  bool|string
	 *
	 * @since  2.0.0
	 */
	public function find_class($type, $path)
	{
		// Fail if not routable
		if ( ! $this->routable)
		{
			return false;
		}
		// If the routable property is a string then this requires a trigger
		// segment to be routable (and all routes will be relative to the trigger)
		elseif (is_string($this->routable))
		{
			// If string trigger isn't found at the beginning return false
			if (strpos(strtolower($path), strtolower($this->routable).'/') !== 0)
			{
				return false;
			}
			// Strip trigger from controller name
			$path = substr($path, strlen($this->routable) + 1);
		}

		// Build the namespace for the controller
		$namespace = $this->namespace;
		if ($pos = strpos($path, '/'))
		{
			$module = substr($path, 0, $pos).'/';
			if (isset($this->modules[$module]))
			{
				$namespace  .= $this->modules[$module];
				$path        = substr($path, $pos + 1);
			}
		}

		$path = $namespace.$this->class_type_prefix($type).str_replace('/', '_', $path);
		if ($this->load_class($path))
		{
			return $path;
		}

		return false;
	}

	/**
	 * Attempts to find a specific file
	 *
	 * @param   string  $location
	 * @param   string  $file
	 * @return  bool|string
	 *
	 * @since  2.0.0
	 */
	public function find_file($location, $file)
	{
		$location  = trim($location, '/\\').'/';

		// if given attempt specific module load
		if (($pos = strpos($file, ':')) !== false)
		{
			$module = substr($file, 0, $pos).'/';
			if (isset($this->modules[$module]))
			{
				if (is_file($path = $this->path.$module.$location.substr($file, $pos + 1)))
				{
					return $path;
				}
			}
			return false;
		}

		// attempt fetch from base
		if (is_file($path = $this->path.$location.$file))
		{
			return $path;
		}

		// attempt to find in modules
		foreach ($this->modules as $path => $ns)
		{
			if (is_file($path = $this->path.'modules/'.$path.$location.$file))
			{
				return $path;
			}
		}

		// all is lost
		return false;
	}
}
