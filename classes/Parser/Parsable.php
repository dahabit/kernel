<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Parser;

/**
 * Parsable Interface
 *
 * Parsable class instances can parse strings and file templates with given input.
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
interface Parsable
{
	/**
	 * Returns expected file extension for Views
	 *
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	public function extension();

	/**
	 * Parses a file using the given variables
	 *
	 * @param   string  $path
	 * @param   array   $data
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	public function parse_file($path, array $data = array());

	/**
	 * Parses a given string using the given variables
	 *
	 * @param   string  $string
	 * @param   array   $data
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	public function parse_string($string, array $data = array());
}
