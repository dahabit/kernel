<?php

namespace Fuel\Kernel\Security\Csrf;
use Fuel\Kernel\Application;

abstract class Base
{
	/**
	 * @var  \Fuel\Kernel\Application\Base  app that created this
	 */
	protected $app;

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param  \Fuel\Kernel\Application\Base  $app
	 */
	public function _set_app(Application\Base $app)
	{
		$this->app = $app;
	}

	/**
	 * Fetch the CSRF token to submit the next request
	 *
	 * @return  string
	 */
	abstract public function get_token();

	/**
	 * Checks if given token is valid, when none given take it from Input
	 *
	 * @param   string  $token
	 * @return  bool
	 */
	abstract public function check_token($token = null);

	/**
	 * Updates the token when necessary
	 *
	 * @param   bool  $force_reset
	 */
	abstract public function update_token($force_reset = false);
}
