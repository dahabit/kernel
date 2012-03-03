<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Security;
use Fuel\Kernel\Application;

/**
 * Base Security class
 *
 * Container for various Security handlers.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
class Base
{
	/**
	 * @var  \Fuel\Kernel\Application\Base
	 *
	 * @since  2.0.0
	 */
	protected $app;

	/**
	 * @var  \Fuel\Kernel\Security\Crypt\Cryptable
	 *
	 * @since  2.0.0
	 */
	public $crypt;

	/**
	 * @var  \Fuel\Kernel\Security\Csrf\Base
	 *
	 * @since  2.0.0
	 */
	public $csrf;

	/**
	 * @var  \Fuel\Kernel\Security\String\Base
	 *
	 * @since  2.0.0
	 */
	public $string;

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
	}

	/**
	 * Returns the App's Crypt instance
	 *
	 * @return  Crypt\Cryptable
	 *
	 * @since  2.0.0
	 */
	public function crypt()
	{
		if (empty($this->crypt))
		{
			$this->crypt = $this->app->forge('Security_Crypt');
		}
		return $this->crypt;
	}

	/**
	 * Returns the App's Csrf instance
	 *
	 * @return  Csrf\Base
	 *
	 * @since  2.0.0
	 */
	public function csrf()
	{
		if (empty($this->csrf))
		{
			$this->csrf = $this->app->forge('Security_Csrf');
		}
		return $this->csrf;
	}

	/**
	 * Returns the App's String cleaner instance
	 *
	 * @return  String\Base
	 *
	 * @since  2.0.0
	 */
	public function string()
	{
		if (empty($this->string))
		{
			$this->string = $this->app->forge('Security_String');
		}
		return $this->string;
	}

	/**
	 * Separate method for cleaning the URI
	 *
	 * @param   string  $uri
	 * @return  string
	 *
	 * @since  1.1.0
	 */
	public function clean_uri($uri)
	{
		return $this->clean($uri);
	}

	/**
	 * Clean a variable with the String cleaner
	 *
	 * @param   mixed  $input
	 * @return  mixed
	 *
	 * @since  1.0.0
	 */
	public function clean($input)
	{
		return $this->string()->clean($input);
	}

	/**
	 * Fetch the CSRF token
	 *
	 * @return string
	 *
	 * @since  1.0.0
	 */
	public function get_token()
	{
		return $this->csrf()->get_token();
	}

	/**
	 * Check the CSRF token
	 *
	 * @param   null|string  $token
	 * @return  bool
	 *
	 * @since  1.0.0
	 */
	public function check_token($token = null)
	{
		return $this->csrf()->check_token($token);
	}
}
