<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Response;

/**
 * Responsible Interface
 *
 * Instances of classes that implement this can be returned as a valid Response
 * object from a Controller.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
interface Responsible
{
	/**
	 * Constructor must take a body, status and an array of headers
	 *
	 * @param  mixed  $body
	 * @param  int    $status
	 * @param  array  $headers
	 *
	 * @since  1.0.0
	 */
	public function __construct($body = '', $status = 200, array $headers = array());

	/**
	 * Must return the body of the response
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public function body();

	/**
	 * Send the response HTTP headers
	 *
	 * @return  Responsible
	 *
	 * @since  1.0.0
	 */
	public function send_headers();

	/**
	 * Output the string response
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public function __toString();
}
