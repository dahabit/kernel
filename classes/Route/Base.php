<?php

namespace Fuel\Kernel\Route;
use Fuel\Kernel\Application;

abstract class Base
{
	/**
	 * @var  \Fuel\Kernel\Application\Base
	 */
	protected $app;

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param  \Fuel\Kernel\Application\Base  $app
	 */
	public function _set_app(Application\Base $app)
	{
		$this->app = $app;
	}

	/**
	 * Checks if the uri matches this route
	 *
	 * @param   string  $uri
	 * @return  bool    whether it matched
	 */
	abstract public function matches($uri);

	/**
	 * Return an array with 1. callable to be the controller and 2. additional params array
	 *
	 * @return  array(callback, params)
	 */
	abstract public function match();
}
