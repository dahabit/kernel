<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Cookie;
use Fuel\Kernel\Application;

/**
 * Base Cookie class
 *
 * Deals with setting, unsetting and reading cookies.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
class Base
{
	/**
	 * @var  \Fuel\Kernel\Application\Base  app that created this request
	 *
	 * @since  2.0.0
	 */
	public $app;

	/**
	 * @var  \Fuel\Kernel\Data\Config
	 */
	public $config;

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param   \Fuel\Kernel\Application\Base  $app
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function _set_app(Application\Base $app)
	{
		$this->app = $app;

		// Check if already created
		try
		{
			$this->config = $app->get_object('Config', 'cookie');
		}
		catch (\RuntimeException $e)
		{
			$this->config = $app->forge('Config');
		}

		$this->config
			// Set defaults
			->add(array(
				'lifetime'   => 0,
				'path'       => '/',
				'domain'     => null,
				'secure'     => false,
				'http_only'  => false,
			))
			// Add validators
			->validators(array(
				'lifetime'   => 'is_int',
				'secure'     => 'is_bool',
				'http_only'  => 'is_bool',
			));
	}

	/**
	 * Fetches a cookie value from the active request input or environmental input
	 *
	 * @param   string  $name
	 * @param   mixed   $default
	 * @return  mixed
	 */
	public function get($name, $default = null)
	{
		$input = ($req = $this->app->active_request()) ? $req->input : $this->app->env->input;
		return $input->cookie($name, $default);
	}

	/**
	 * Set a new cookie
	 *
	 * @param   string  $name
	 * @param   string  $value
	 * @param   int     $lifetime
	 * @param   string  $path
	 * @param   string  $domain
	 * @param   bool    $secure     if true, the cookie should only be transmitted over a secure HTTPS connection
	 * @param   bool    $http_only  if true, the cookie will be made accessible only through the HTTP protocol
	 * @return  bool
	 */
	public function set($name, $value, $lifetime = null, $path = null, $domain = null, $secure = null, $http_only = null)
	{
		$value = __val($value);

		// use the class defaults for the other parameters if not provided
		is_null($lifetime)
			and $lifetime = $this->config->get('lifetime');
		is_null($path)
			and $path = $this->config->get('path');
		is_null($domain)
			and $domain = $this->config->get('domain');
		is_null($secure)
			and $secure = $this->config->get('secure');
		is_null($http_only)
			and $http_only = $this->config->get('http_only');

		// add the current time so we have an offset
		$expiration = $lifetime > 0 ? $lifetime + time() : 0;

		return setcookie($name, $value, $expiration, $path, $domain, $secure, $http_only);
	}

	/**
	 * Deletes a cookie by making the value null and expiring it
	 *
	 * @param   string  $name
 	 * @param   string  $path
	 * @param   string  $domain
	 * @param   bool    $secure     if true, the cookie should only be transmitted over a secure HTTPS connection
	 * @param   bool    $http_only  if true, the cookie will be made accessible only through the HTTP protocol
	 * @return  bool
	 */
	public function delete($name, $path = null, $domain = null, $secure = null, $http_only = null)
	{
		unset($_COOKIE[$name]);
		return $this->set($name, null, -86400, $path, $domain, $secure, $http_only);
	}
}