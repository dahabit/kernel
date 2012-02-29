<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Security\String;
use Fuel\Kernel\Application;

/**
 * Base String Security class
 *
 * Basis for classes dealing with securing strings.
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
	 * Secure string, object or array
	 *
	 * @param   mixed  $input
	 * @return  mixed
	 * @throws  \RuntimeException
	 *
	 * @since  2.0.0
	 */
	public function secure($input)
	{
		static $already_cleaned = array();

		// Nothing to escape for non-string scalars, or for already processed values
		if (is_bool($input) or is_int($input) or is_float($input) or in_array($input, $already_cleaned, true))
		{
			return $input;
		}

		if (is_string($input))
		{
			$input = $this->clean($input);
		}
		elseif (is_array($input) or ($input instanceof \Iterator and $input instanceof \ArrayAccess))
		{
			// Add to $already_cleaned variable when object
			is_object($input) and $already_cleaned[] = $input;

			foreach ($input as $k => $v)
			{
				$input[$k] = $this->secure($v);
			}
		}
		elseif ($input instanceof \Iterator or get_class($input) == 'stdClass')
		{
			// Add to $already_cleaned variable
			$already_cleaned[] = $input;

			foreach ($input as $k => $v)
			{
				$input->{$k} = $this->secure($v);
			}
		}
		elseif (is_object($input))
		{
			// Check if the object is whitelisted and return when that's the case
			foreach ($this->app->config->get('security.whitelisted_classes', array()) as $class)
			{
				if (is_a($input, $class))
				{
					// Add to $already_cleaned variable
					$already_cleaned[] = $input;

					return $input;
				}
			}

			// Throw exception when it wasn't whitelisted and can't be converted to String
			if ( ! method_exists($input, '__toString'))
			{
				throw new \RuntimeException('Object class "'.get_class($input).'" could not be converted to string or '.
					'sanitized as ArrayAccess. Whitelist it in security.whitelisted_classes in app/config/config.php '.
					'to allow it to be passed unchecked.');
			}

			$input = $this->secure((string) $input);
		}

		return $input;
	}

	/**
	 * Clean string
	 *
	 * @param   string
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	abstract public function clean($input);
}
