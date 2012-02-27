<?php

namespace Fuel\Kernel\Request;
use Fuel\Kernel\Application;

abstract class Base
{
	/**
	 * @var  Base  request that created this one
	 */
	protected $parent;

	/**
	 * @var  array  requests that were created during this one
	 */
	protected $descendants = array();

	/**
	 * @var  array  active Request stack before activation of this one
	 */
	protected $_before_activate = array();

	/**
	 * @var  \Fuel\Kernel\Application\Base  app that created this request
	 */
	public $app;

	/**
	 * @var  \Fuel\Kernel\Input
	 */
	public $input;

	/**
	 * @var  \Fuel\Kernel\Response\Responsible  Response after execution
	 */
	public $response;

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param  \Fuel\Kernel\Application\Base  $app
	 */
	public function _set_app(Application\Base $app)
	{
		$this->app = $app;

		// Set request tree references
		$this->parent = $this->app->active_request();
		$this->parent and $this->parent->set_descendant($this);

		// Default the Input object to the environment
		$this->input = _env('input');
	}

	/**
	 * Makes this Request the active one
	 *
	 * @return  Base  for method chaining
	 */
	public function activate()
	{
		array_push($this->_before_activate, $this->app->active_request());
		$this->app->set_active_request($this);
		return $this;
	}

	/**
	 * Deactivates this Request and reactivates the previous active
	 *
	 * @return  Base  for method chaining
	 */
	public function deactivate()
	{
		$this->app->set_active_request(array_pop($this->_before_activate));
		return $this;
	}

	/**
	 * Returns the request that created this one
	 *
	 * @return  Base
	 */
	public function get_parent()
	{
		return $this->parent;
	}

	/**
	 * Adds a descendant to the current Request
	 *
	 * @param  Base  $request
	 */
	public function set_descendant(Base $request)
	{
		$this->descendants[] = $request;
	}

	/**
	 * Returns the array of requests created during this one
	 *
	 * @return  array
	 */
	public function get_descendants()
	{
		return $this->descendants;
	}

	/**
	 * Return the Input for this object
	 *
	 * @return  \Fuel\Kernel\Input
	 */
	public function input()
	{
		return $this->input;
	}

	/**
	 * Execute the request
	 *
	 * Must use $this->activate() as the first statement and $this->deactivate() as the last one
	 *
	 * @return  Base
	 */
	abstract public function execute();

	/**
	 * Fetch the request response after execution
	 */
	public function response()
	{
		return $this->response;
	}
}
