<?php

namespace Fuel\Kernel\Parser;

interface Parsable
{
	/**
	 * Returns expected file extension for Views
	 *
	 * @return  string
	 */
	public function extension();

	/**
	 * Parses a file using the given variables
	 *
	 * @param   string  $path
	 * @param   array   $data
	 * @return  string
	 */
	public function parse_file($path, array $data = array());

	/**
	 * Parses a given string using the given variables
	 *
	 * @param   string  $string
	 * @param   array   $data
	 * @return  string
	 */
	public function parse_string($string, array $data = array());
}
