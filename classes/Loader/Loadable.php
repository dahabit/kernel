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
 * Loadable Interface
 *
 * Loadable class instances can load classes for the autoloader as well as files.
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
interface Loadable
{
	/**
	 * Fuel method that is the setter for the app's environment
	 *
	 * @param   \Fuel\Kernel\Environment  $env
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function _set_env(Environment $env);

	/**
	 * Returns the base path for this package
	 *
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	public function path();

	/**
	 * Classloader
	 *
	 * @param   string  $class
	 * @return  bool    success of load operation
	 *
	 * @since  2.0.0
	 */
	public function load_class($class);

	/**
	 * Set routability of controller
	 *
	 * @param   bool|string  $routable
	 * @return  Loadable
	 *
	 * @since  2.0.0
	 */
	public function set_routable($routable);

	/**
	 * Locate specialized classes like Controllers & Tasks and load them
	 *
	 * @param   string  $type
	 * @param   string  $path
	 * @return  bool|string  classname or false for failure
	 *
	 * @since  2.0.0
	 */
	public function find_class($type, $path);

	/**
	 * Locate file
	 *
	 * @param   string  $location
	 * @param   string  $file
	 * @return  bool|string  full path or false for failure
	 *
	 * @since  2.0.0
	 */
	public function find_file($location, $file);
}
