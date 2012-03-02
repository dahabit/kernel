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
use Classes;

/**
 * Fuel Route class
 *
 * Default Route object class used in Fuel.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.1.0
 */
class Fuel extends Classes\Route\Base
{
	/**
	 * @var  array  HTTP methods
	 *
	 * @since  1.1.0
	 */
	protected $methods = array();

	/**
	 * @var  string  uri this must match
	 *
	 * @since  1.1.0
	 */
	protected $search = '';

	/**
	 * @var  string  uri it translates to
	 *
	 * @since  1.1.0
	 */
	protected $translation = '';

	/**
	 * @var  callback  something callable that matched
	 *
	 * @since  2.0.0
	 */
	protected $match;

	/**
	 * @var  array  URI segments
	 *
	 * @since  2.0.0
	 */
	protected $segments = array();

	/**
	 * Constructor
	 *
	 * @param  string|\Closure       $search
	 * @param  null|string|\Closure  $translation
	 * @param  array                 $methods
	 *
	 * @since  1.0.0
	 */
	public function __construct($search, $translation = null, array $methods = array())
	{
		$this->methods = $methods;

		$this->search = $search;
		if (is_string($this->search))
		{
			// The search uri may start with allowed methods 'DELETE ' or multiple 'GET|POST|PUT '
			if (preg_match('#^(GET\\|?|POST\\|?|PUT\\|?|DELETE\\|?)+ #uD', $this->search, $matches))
			{
				$this->search   = ltrim(substr($this->search, strlen($matches[0])), '/ ');
				$this->methods  = array_unique(
					array_merge($this->methods, explode('|', trim($matches[0])))
				);
			}
			$this->search = '/'.trim($this->search, '/ ');
		}

		$this->translation = is_null($translation) ? $this->search : $translation;
		if (is_string($this->translation))
		{
			$this->translation = '/'.trim($this->translation, '/ ');
		}
	}

	/**
	 * Checks if the uri matches this route
	 *
	 * @param   string  $uri
	 * @return  bool    whether it matched
	 *
	 * @since  2.0.0
	 */
	public function matches($uri)
	{
		$request = $this->app->active_request();
		if ( ! empty($this->methods) and ! in_array(strtoupper($request->input->method()), $this->methods))
		{
			return false;
		}

		if ($this->search instanceof \Closure)
		{
			// Given translation is superseded by the callback output when not just boolean true
			$translation = call_user_func($this->search, $uri, $this->app, $request);
			$translation === true and $translation = $this->translation;

			if ($translation)
			{
				return $this->parse($translation);
			}
		}
		elseif (is_string($this->search))
		{
			$translation = preg_replace('#^'.$this->search.'$#uD', $this->translation, $uri, -1, $count);
			if ($count)
			{
				return $this->parse($translation);
			}
		}

		// Failure...
		return false;
	}

	/**
	 * Attempts to find the controller and returns success
	 *
	 * @param   string  $translation
	 * @return  bool
	 *
	 * @since  1.1.0
	 */
	protected function parse($translation)
	{
		// Return directly if it's a Closure or a callable array
		if ($translation instanceof \Closure
			or (is_array($translation) and is_callable($translation)))
		{
			return true;
		}

		// Return Controller when found
		if (is_string($translation) and ($controller = $this->find_class($translation)))
		{
			$this->match = $this->app->forge($controller);
			return true;
		}

		// Failure...
		return false;
	}

	/**
	 * Parses the URI into a controller class
	 *
	 * @param   $uri
	 * @return  bool|string
	 *
	 * @since  2.0.0
	 */
	protected function find_class($uri)
	{
		$uri_array = explode('/', trim($uri, '/'));
		$uri_array = array_map(function($val) { return ucfirst(strtolower($val)); }, $uri_array);
		while ($uri_array)
		{
			if ($controller = $this->app->find_class('Controller', implode('/', $uri_array)))
			{
				return $controller;
			}
			array_unshift($this->segments, array_pop($uri_array));
		}
		return false;
	}

	/**
	 * Return an array with 1. callable to be the controller and 2. additional params array
	 *
	 * @return  array(callback, params)
	 *
	 * @since  2.0.0
	 */
	public function match()
	{
		return array($this->match, $this->segments);
	}
}
