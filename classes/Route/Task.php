<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Route;
use Fuel\Kernel\Application;
use Classes;

/**
 * Special Task Route that routes to a Task instead of a Controller.
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
class Task extends Classes\Route\Fuel
{
	/**
	 * @var  \Fuel\Kernel\Cli
	 *
	 * @since  2.0.0
	 */
	protected $cli;

	/**
	 * @var  array  URI segments
	 *
	 * @since  2.0.0
	 */
	protected $segments = array();

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
		$this->app->env->is_cli and $this->cli = $this->app->get_object('Cli');
	}

	/**
	 * Extension of matches() method to only allow using this from the CLI
	 *
	 * @param   string  $uri
	 * @return  bool
	 *
	 * @since  2.0.0
	 */
	public function matches($uri)
	{
		// Disable running these routes outside the commandline
		if (is_null($this->cli))
		{
			return false;
		}

		if (parent::matches($uri))
		{
			return true;
		}

		// Failure...
		return false;
	}

	/**
	 * Parses the URI into a task class
	 *
	 * @param   $uri
	 * @return  string|bool
	 *
	 * @since  2.0.0
	 */
	protected function find_class($uri)
	{
		$uri_array = explode('/', trim($uri, '/'));
		while ($uri_array)
		{
			if ($task = $this->app->find_class('Task', implode('/', $uri_array)))
			{
				return $task;
			}
			array_unshift($this->segments, array_pop($uri_array));
		}
		return false;
	}

	/**
	 * Clear arguments from input
	 *
	 * @param   array  $actions
	 * @return  array
	 *
	 * @since  2.0.0
	 */
	protected function _clear_args($actions = array())
	{
		foreach ($actions as $key => $action)
		{
			if (substr($action, 0, 1) === '-')
			{
				unset($actions[$key]);
			}
		}

		return $actions;
	}
}
