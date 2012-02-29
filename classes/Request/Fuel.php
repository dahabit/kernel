<?php

namespace Fuel\Kernel\Request;
use Fuel\Kernel\Application;
use Fuel\Kernel\Response;

class Fuel extends \Classes\Request\Base
{
	/**
	 * @var  string
	 */
	public $request_uri = '';

	/**
	 * @var  callback
	 */
	public $controller;

	/**
	 * @var  array
	 */
	public $controller_params = array();

	public function __construct($uri = '', array $input = array())
	{
		$this->request_uri  = '/'.trim((string) $uri, '/');
		$this->input        = $input ?: _env('input');
	}

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param  \Fuel\Kernel\Application\Base  $app
	 */
	public function _set_app(Application\Base $app)
	{
		parent::_set_app($app);

		// Create the new Input object when an array was passed
		if (is_array($this->input))
		{
			$this->input = $app->forge('Input', $this->parent ? $this->parent->input : _env('input'));
		}
	}

	/**
	 * Execute the request
	 *
	 * Must use $this->activate() as the first statement and $this->deactivate() as the last one
	 *
	 * @return  Fuel
	 */
	public function execute()
	{
		$this->activate();

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

		$this->deactivate();
		return $this;
	}
}
