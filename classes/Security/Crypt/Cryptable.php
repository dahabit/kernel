<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Security\Crypt;

/**
 * Cryptable interface
 *
 * Interface for class that can deal with hashing, encryption and decryption.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
interface Cryptable
{
	/**
	 * Returns a hash of the given string
	 *
	 * @param  $string
	 *
	 * @since  1.0.0
	 */
	public function hash($string);

	/**
	 * Encrypts the given string
	 *
	 * @param   string  $string
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public function encrypt($string);

	/**
	 * Decrypts the given string
	 *
	 * @param   string  $string
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public function decrypt($string);
}
