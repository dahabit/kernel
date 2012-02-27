<?php

namespace Fuel\Kernel\Loader;

interface Loadable
{
	/**
	 * Classloader
	 *
	 * @param   string  $class
	 * @return  bool    success of load operation
	 */
	public function load_class($class);

	/**
	 * Set routability of controller
	 *
	 * @param  bool|string  $routable
	 */
	public function set_routable($routable);

	/**
	 * Locate specialized classes like Controllers & Tasks and load them
	 *
	 * @param   string  $path
	 * @return  bool|string  classname or false for failure
	 */
	public function find_class($type, $path);

	/**
	 * Locate file
	 *
	 * @param   string  $location
	 * @param   string  $file
	 * @return  bool|string  full path or false for failure
	 */
	public function find_file($location, $file);
}
