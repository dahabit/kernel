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
 * PHP Parser
 *
 * Parse PHP files or strings containing PHP.
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
class Php implements Parsable
{
	/**
	 * Returns the expected file extension
	 *
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	public function extension()
	{
		return 'php';
	}

	/**
	 * Parses a file using the given variables
	 *
	 * @param   string  $path
	 * @param   array   $data
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	public function parse_file($path, array $data = array())
	{
		$clean_room = function($__file_name, array $__data)
		{
			extract($__data, EXTR_REFS);

			// Capture the view output
			ob_start();

			try
			{
				// Load the view within the current scope
				include $__file_name;
			}
			catch (\Exception $e)
			{
				// Delete the output buffer
				ob_end_clean();

				// Re-throw the exception
				throw $e;
			}

			// Get the captured output and close the buffer
			return ob_get_clean();
		};
		return $clean_room($path, $data);
	}

	/**
	 * Parses a given string using the given variables
	 *
	 * @param   string  $template
	 * @param   array   $data
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	public function parse_string($template, array $data = array())
	{
		$clean_room = function($__template, array $__data)
		{
			extract($__data, EXTR_REFS);

			// Capture the view output
			ob_start();

			try
			{
				// Load the view within the current scope
				eval($__template);
			}
			catch (\Exception $e)
			{
				// Delete the output buffer
				ob_end_clean();

				// Re-throw the exception
				throw $e;
			}

			// Get the captured output and close the buffer
			return ob_get_clean();
		};
		return $clean_room($template, $data);
	}
}
