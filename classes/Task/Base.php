<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Task;
use Classes;

/**
 * Base Task implementation: a commandline Controller
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
abstract class Base extends Classes\Controller\Base
{
	/**
	 * @var  \Fuel\Kernel\Cli
	 *
	 * @since  2.0.0
	 */
	protected $cli;

	/**
	 * Makes the CLI object available
	 *
	 * @since  2.0.0
	 */
	public function before()
	{
		$this->cli = $this->app->get_object('Cli');
	}

	/**
	 * Puts any response on the command line
	 *
	 * @param   mixed  $response
	 * @return  \Fuel\Kernel\Response\Base
	 *
	 * @since  2.0.0
	 */
	public function after($response)
	{
		$response = parent::after($response);
		$response->body and $this->cli->write($response->body);

		return $response;
	}
}
