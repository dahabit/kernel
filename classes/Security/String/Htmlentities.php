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

/**
 * HTML entities String Security class
 *
 * Uses htmlentities() to encode strings for safer output.
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
class Htmlentities extends Base
{
	public function clean($input)
	{
		return htmlentities($input, ENT_QUOTES, _env('encoding'), false);
	}
}
