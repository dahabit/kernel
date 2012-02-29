<?php

namespace Fuel\Kernel\DiC;

interface Dependable
{
	/**
	 * @param  \Fuel\Kernel\Application\Base  $app
	 * @param  Dependable  $parent
	 *
	 * @since  2.0.0
	 */
	public function __construct($app = null, $parent = null);

	/**
	 * Add a class to use for a given classname
	 *
	 * @param   string  $classname
	 * @param   string  $actual
	 * @return  Dependable
	 *
	 * @since  2.0.0
	 */
	public function set_class($classname, $actual);

	/**
	 * Add multiple classes to use for classnames
	 *
	 * @param   array  $classnames
	 * @return  Dependable
	 *
	 * @since  2.0.0
	 */
	public function set_classes(array $classnames);

	/**
	 * Get an actual class for a given classname
	 *
	 * @param   string  $class
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	public function get_class($class);

	/**
	 * Create an object of a given classname
	 *
	 * @param   string  $classname
	 * @return  object
	 *
	 * @since  2.0.0
	 */
	public function forge($classname);

	/**
	 * Add an object to the container
	 *
	 * @param   string  $classname
	 * @param   string  $name
	 * @param   object  $instance
	 * @return  Dependable
	 *
	 * @since  2.0.0
	 */
	public function set_object($classname, $name, $instance);

	/**
	 * Fetch an object from the container
	 *
	 * @param   string  $classname
	 * @param   string  $name
	 * @return  object
	 *
	 * @since  2.0.0
	 */
	public function get_object($classname, $name = null);
}
