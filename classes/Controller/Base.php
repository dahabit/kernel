<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Controller;
use Fuel\Kernel\Application;
use Fuel\Kernel\Request;
use Fuel\Kernel\Response;

/**
 * Controller Base class
 *
 * Default controller class that takes action based on the input it gets.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
abstract class Base
{
	/**
	 * @var  string  default method to call on empty action input
	 *
	 * @since  2.0.0
	 */
	protected static $default_action = 'index';

	/**
	 * @var  string  required prefix for method to be accessible as action
	 *
	 * @since  2.0.0
	 */
	protected static $action_prefix = 'action_';

	/**
	 * @var  \Fuel\Kernel\Application\Base
	 *
	 * @since  2.0.0
	 */
	public $app;

	/**
	 * @var  \Fuel\Kernel\Loader\Loadable
	 *
	 * @since  2.0.0
	 */
	public $loader;

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param   \Fuel\Kernel\Application\Base  $app
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function _set_app(Application\Base $app)
	{
		$this->app = $app;

		$this->app->get_object('Log')->info('Controller created.', __METHOD__);
	}

	/**
	 * Executes the given method and returns a Responsible object
	 *
	 * @param   \ReflectionMethod|string  $method
	 * @param   array  $args
	 * @return  \Fuel\Kernel\Response\Responsible
	 *
	 * @since  2.0.0
	 */
	public function execute($method, array $args = array())
	{
		$this->app->get_object('Log')->info('Controller execution started.', __METHOD__);
		! $method instanceof \ReflectionMethod and $method = new \ReflectionMethod($this, $method);

		$this->before();
		$response = $method->invokeArgs($this, $args);
		$response = $this->after($response);

		$this->app->get_object('Log')->info('Controller execution finished.', __METHOD__);
		return $response;
	}

	/**
	 * Method to execute for controller setup
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	public function before() {}

	/**
	 * Method to execute for finishing up controller execution, ensures the response is a Response object
	 *
	 * @param   mixed  $response
	 * @return  \Fuel\Kernel\Response\Base
	 *
	 * @since  1.0.0
	 */
	public function after($response)
	{
		if ( ! $response instanceof Response\Base)
		{
			$response = $this->app->forge('Response', $response);
		}

		return $response;
	}

	/**
	 * Makes the Controller instance executable, must be given the URI segments to continue
	 *
	 * @param    array  $args
	 * @return  \Fuel\Kernel\Response\Responsible
	 * @throws  \Fuel\Kernel\Request\Exception_404
	 *
	 * @since  2.0.0
	 */
	public function __invoke(array $args)
	{
		// Determine the method
		$method = static::$action_prefix.(array_shift($args) ?: static::$default_action);

		// Return false if it doesn't exist
		if ( ! method_exists($this, $method))
		{
			throw new Request\Exception_404('No such action "'.$method.'" in Controller: '.get_class($this));
		}

		/**
		 * Return false if the method isn't public
		 */
		$method = new \ReflectionMethod($this, $method);
		if ( ! $method->isPublic())
		{
			throw new Request\Exception_404('Unavailable action "'.$method.'" in Controller: '.get_class($this));
		}

		return $this->execute($method, $args);
	}
}
