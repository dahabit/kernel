<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Route;
use Fuel\Kernel\Application;

/**
 * Base Route class
 *
 * Basis for a Fuel Route object.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.1.0
 */
abstract class Base
{
	/**
	 * @var  \Fuel\Kernel\Application\Base
	 *
	 * @since  2.0.0
	 */
	protected $app;

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param  \Fuel\Kernel\Application\Base  $app
	 *
	 * @since  2.0.0
	 */
	public function _set_app(Application\Base $app)
	{
		$this->app = $app;
	}

	/**
	 * Checks if the uri matches this route
	 *
	 * @param   string  $uri
	 * @return  bool    whether it matched
	 *
	 * @since  2.0.0
	 */
	abstract public function matches($uri);

	/**
	 * Return an array with 1. callable to be the controller and 2. additional params array
	 *
	 * @return  array(callback, params)
	 *
	 * @since  2.0.0
	 */
	abstract public function match();
}
