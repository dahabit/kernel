<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Security\Csrf;
use Fuel\Kernel\Application;

/**
 * Base CSRF Security class
 *
 * Basis for classes dealing with tokens to secure against CSRF attacks.
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
abstract class Base
{
	/**
	 * @var  \Fuel\Kernel\Application\Base  app that created this
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
	 * Fetch the CSRF token to submit the next request
	 *
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	abstract public function get_token();

	/**
	 * Checks if given token is valid, when none given take it from Input
	 *
	 * @param   string  $token
	 * @return  bool
	 *
	 * @since  2.0.0
	 */
	abstract public function check_token($token = null);

	/**
	 * Updates the token when necessary
	 *
	 * @param   bool  $force_reset
	 * @return  Base
	 *
	 * @since  2.0.0
	 */
	abstract public function update_token($force_reset = false);
}
