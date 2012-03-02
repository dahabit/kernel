<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Core\Loader;
use Fuel\Kernel\Environment;
use Classes\Loader\Loadable;

/**
 * Composer Loader
 *
 * Fuel loader object that autoloads Composer installed classes
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
class Composer implements Loadable
{
	/**
	 * @var  \Fuel\Kernel\Environment
	 */
	protected $env;

	/**
	 * @var  array  class mappings gotten from Composer
	 *
	 * @since  2.0.0
	 */
	protected $mappings = array();

	/**
	 * Constructor
	 *
	 * @param  null|string  $path  where to find the Composer autoload namespaces file
	 *
	 * @since  2.0.0
	 */
	public function __construct($path = null)
	{
		$this->mappings = $path;
	}

	/**
	 * Fuel method that is the setter for the app's environment
	 *
	 * @param   \Fuel\Kernel\Environment  $env
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function _set_env(Environment $env)
	{
		$this->env = $env;

		// When no mapping path set or mapping array given, use default
		is_null($this->mappings)
			and $this->mappings = $env->path('fuel').'_composer/.composer/autoload_namespaces.php';

		// When the mappings property is still a string it's a path to be required
		is_string($this->mappings)
			and $this->mappings = require $this->mappings;

		// Show package loads inside application
		($app = $env->active_application())
			and $app->get_object('Log')->info('Composer Loader initialized.', __METHOD__);
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
		return $this->env->path('fuel');
	}

	/**
	 * Attempts to load the class
	 *
	 * @param   string  $class
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	public function load_class($class)
	{
		foreach ($this->mappings as $namespace => $path)
		{
			// Attempt to match the "namespace" and if it does try to load the file
			// from the given path.
			if (strncmp($namespace, $class, strlen($namespace)) == 0
				and file_exists($file = $this->class_to_path($class, $path)))
			{
				require $file;
				return true;
			}
		}

		// Failure...
		return false;
	}

	/**
	 * Converts a classname to a path using PSR-0 conventions
	 *
	 * @param   string  $class
	 * @param   string  $basepath
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	protected function class_to_path($class, $basepath)
	{
		$file  = '';
		if ($last_ns_pos = strripos($class, '\\'))
		{
			$namespace = substr($class, 0, $last_ns_pos);
			$class = substr($class, $last_ns_pos + 1);
			$file = str_replace('\\', '/', $namespace).'/';
		}
		$file .= str_replace('_', '/', $class).'.php';

		return rtrim($basepath, '/\\').'/'.$file;
	}

	/**
	 * Whatever happens, this does nothing
	 *
	 * @param   bool  $routable
	 * @return  bool
	 *
	 * @since  2.0.0
	 */
	public function set_routable($routable)
	{
		return $this;
	}

	/**
	 * Disable finding controllers
	 *
	 * @param   string  $type
	 * @param   string  $path
	 * @return  bool|string
	 *
	 * @since  2.0.0
	 */
	public function find_class($type, $path)
	{
		return false;
	}

	/**
	 * Disable finding files
	 *
	 * @param   string  $location
	 * @param   string  $file
	 * @param   string  $basepath
	 * @return  bool|string
	 *
	 * @since  2.0.0
	 */
	public function find_file($location, $file, $basepath = null)
	{
		return false;
	}
}
