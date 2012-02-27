<?php

namespace Fuel\Kernel\Data;

class Language extends \Classes\Data\Base
{
	/**
	 * @var  \Fuel\Kernel\Parser\Parsable
	 */
	protected $parser;

	/**
	 * Load language file
	 *
	 * @param   string  $file
	 * @return  Config
	 */
	public function load($file, $language = null)
	{
		$language = $language ?: _env('language');
		$files = $this->_app->find_files('language/'.$language, $file);
		foreach ($files as $file)
		{
			$array = require $file;
			$this->_data = array_merge($this->_data, $array);
		}
		return $this;
	}

	/**
	 * Fetch the language Parser
	 *
	 * @return  \Fuel\Kernel\Parser\Parsable
	 */
	public function parser()
	{
		if ( ! $this->parser)
		{
			$this->parser = $this->_app->get_object('Parser');
		}
		return $this->parser;
	}

	/**
	 * Fetch a language string and replace some variables
	 *
	 * @param   string  $string
	 * @param   array   $values
	 * @return  string
	 */
	public function parse($string, array $values = array(), $default = null)
	{
		return ($string = $this->get($string)) ? $this->parser()->parse_string($string, $values) : $default;
	}
}