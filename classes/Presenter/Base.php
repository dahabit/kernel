<?php

namespace Fuel\Kernel\Presenter;
use Fuel\Kernel\Application;
use Fuel\Kernel\View;

abstract class Base extends View\Base
{
	/**
	 * @var  \Fuel\Kernel\Loader\Loadable
	 */
	protected $_loader;

	/**
	 * @var  string|null  method to be run upon the Presenter, nothing will be ran when null
	 */
	protected $_method = 'view';

	public function __construct()
	{
		empty($this->_path) and $this->default_path();
		$this->before();
	}

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param  \Fuel\Kernel\Application\Base  $app
	 */
	public function _set_app(Application\Base $app)
	{
		parent::_set_app($app);
		$this->_loader = $app->loader;
	}

	/**
	 * Generates the View path based on the Presenter classname
	 *
	 * @return  Base
	 */
	public function default_path()
	{
		$class = get_class($this);
		if (($pos = strpos($class, 'Presenter\\')) !== false)
		{
			$class = substr($class, $pos + 10);
		}
		$this->_path = str_replace('\\', '/', strtolower($class));

		return $this;
	}

	/**
	 * Method to do general Presenter setup
	 */
	public function before() {}

	/**
	 * Default method that'll be run upon the Presenter
	 */
	abstract public function view();

	/**
	 * Method to do general Presenter finishing up
	 */
	public function after() {}

	/**
	 * Extend render() to execute the Presenter methods
	 *
	 * @param null $method
	 * @return string
	 */
	protected function render($method = null)
	{
		// Run a specific given method and finish up with after()
		if ($method !== null)
		{
			$this->{$method}();
			$this->after();
		}
		// Run this Presenter's main method, finish up with after() and prevent is from being run again
		elseif ( ! empty($this->_method))
		{
			$this->{$this->_method}();
			$this->_method = null;
			$this->after();
		}

		return parent::render();
	}
}
