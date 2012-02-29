<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\View;

/**
 * Viewable interface
 *
 * Minimal requirements for a class to be considered a viewable object.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
interface Viewable
{
	/**
	 * Setter: One must be able to set any variable upon the Viewable
	 *
	 * @param   $name
	 * @param   $value
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function __set($name, $value);

	/**
	 * Getter: One must be able to get variables from the Viewable
	 *
	 * @param   $name
	 * @return  mixed
	 *
	 * @since  1.0.0
	 */
	public function & __get($name);

	/**
	 * The Viewable must be able to turn into a string
	 *
	 * @since  1.0.0
	 */
	public function __toString();
}
