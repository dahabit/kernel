<?php

namespace Fuel\Kernel\Parser;

class Php implements Parsable
{
	/**
	 * Returns the expected file extension
	 *
	 * @return  string
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
	 * @param   string  $string
	 * @param   array   $data
	 * @return  string
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
