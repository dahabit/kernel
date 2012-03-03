<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Request;
use Fuel\Kernel\Application;
use Fuel\Kernel\Input;
use Fuel\Kernel\Response;

/**
 * Fuel Request class
 *
 * Default implementation of the Request base class for Fuel.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
class Fuel extends \Classes\Request\Base
{
	/**
	 * @var  string
	 *
	 * @since  2.0.0
	 */
	public $request_uri = '';

	/**
	 * @var  callback
	 *
	 * @since  2.0.0
	 */
	public $controller;

	/**
	 * @var  array
	 *
	 * @since  2.0.0
	 */
	public $controller_params = array();

	/**
	 * Constructor
	 *
	 * @param  string  $uri
	 * @param  array|\Fuel\Kernel\Input  $input
	 *
	 * @since  1.0.0
	 */
	public function __construct($uri = '', $input = null)
	{
		$this->request_uri  = '/'.trim((string) $uri, '/');
		$this->input        = $input;
	}

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
		parent::_set_app($app);

		// Create the new Input object when an array was passed
		if (is_array($this->input))
		{
			$this->input = $app->forge('Input', $this->parent ? $this->parent->input : $this->app->env->input);
		}

		// If there's no valid input object as input: default to environment input
		if ( ! $this->input instanceof Input)
		{
			$this->input = $this->app->env->input;
		}
	}

	/**
	 * Execute the request
	 *
	 * Must use $this->activate() as the first statement and $this->deactivate() as the last one
	 *
	 * @return  Fuel
	 *
	 * @since  1.0.0
	 */
	public function execute()
	{
		$this->activate();
		$this->app->notifier->notify('request_started', $this, __METHOD__);

		list($this->controller, $this->controller_params) = $this->app->process_route($this->request_uri);

		if ( ! is_callable($this->controller))
		{
			throw new \DomainException('The Controller returned by routing is not callable.');
		}

		$this->response = call_user_func($this->controller, $this->controller_params);
		if ( ! $this->response instanceof Response\Responsible)
		{
			throw new \DomainException('Result from a Controller must implement the Responsible interface.');
		}

		$this->app->notifier->notify('request_finished', $this, __METHOD__);
		$this->deactivate();
		return $this;
	}
}
