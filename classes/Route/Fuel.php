<?php

namespace Fuel\Kernel\Route;
use Classes;

class Fuel extends Classes\Route\Base
{
	/**
	 * @var  array  HTTP methods
	 */
	protected $methods = array();

	/**
	 * @var  string  uri this must match
	 */
	protected $search = '';

	/**
	 * @var  string  uri it translates to
	 */
	protected $translation = '';

	/**
	 * @var  callback  something callable that matched
	 */
	protected $match;

	/**
	 * @var  array  URI segments
	 */
	protected $segments = array();

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
			$this->match = array($this->app->forge($controller), 'router');
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
	 */
	protected function find_class($uri)
	{
		$uri_array = explode('/', trim($uri, '/'));
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
	 */
	public function match()
	{
		return array($this->match, $this->segments);
	}
}
