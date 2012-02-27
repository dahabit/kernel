<?php

namespace Fuel\Kernel\Security\Crypt;

interface Cryptable
{
	/**
	 * Returns a hash of the given string
	 *
	 * @param  $string
	 */
	public function hash($string);

	/**
	 * Encrypts the given string
	 *
	 * @param   string  $string
	 * @return  string
	 */
	public function encrypt($string);

	/**
	 * Decrypts the given string
	 *
	 * @param   string  $string
	 * @return  string
	 */
	public function decrypt($string);
}
