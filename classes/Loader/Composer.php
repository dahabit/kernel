<?php

namespace Fuel\Core\Loader;
use Classes\Loader\Loadable;

class Composer implements Loadable
{
	/**
	 * @var  array  class mappings gotten from Composer
	 */
	protected $mappings = array();

	public function __construct($path = null)
	{
		is_null($path) and $path = _env()->path('fuel').'_composer/.composer/autoload_namespaces.php';
		$this->mappings = require $path;
	}

	/**
	 * Returns the base path for this package
	 *
	 * @return  string
	 */
	public function path()
	{
		return _env()->path('fuel');
	}

	/**
	 * Attempts to load the class
	 *
	 * @param   string  $class
	 * @return  string
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

		return $basepath.$file;
	}

	/**
	 * Whatever happens, this does nothing
	 *
	 * @param   bool  $routable
	 * @return  bool
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
	 */
	public function find_file($location, $file, $basepath = null)
	{
		return false;
	}
}
