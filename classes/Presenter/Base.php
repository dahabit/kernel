<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Presenter;
use Fuel\Kernel\Application;
use Fuel\Kernel\View;

/**
 * Presenter
 *
 * Extension of Viewable that allows you to add methods to a View that get executed before
 * it is parsed.
 * This is a reimplementation of ViewModel.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
abstract class Base extends View\Base
{
	/**
	 * @var  \Fuel\Kernel\Loader\Loadable
	 *
	 * @since  2.0.0
	 */
	protected $_loader;

	/**
	 * @var  string|null  method to be run upon the Presenter, nothing will be ran when null
	 *
	 * @since  2.0.0
	 */
	protected $_method = 'view';

	/**
	 * Constructor
	 */
	public function __construct()
	{
		empty($this->_path) and $this->default_path();
		$this->before();
	}

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param  \Fuel\Kernel\Application\Base  $app
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
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
	 *
	 * @since  1.0.0
	 */
	public function before() {}

	/**
	 * Default method that'll be run upon the Presenter
	 *
	 * @since  1.0.0
	 */
	abstract public function view();

	/**
	 * Method to do general Presenter finishing up
	 *
	 * @since  1.0.0
	 */
	public function after() {}

	/**
	 * Extend render() to execute the Presenter methods
	 *
	 * @param null $method
	 * @return string
	 *
	 * @since  1.0.0
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
