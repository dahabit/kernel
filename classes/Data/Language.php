<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Data;

/**
 * Language class
 *
 * Language lines container.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
class Language extends \Classes\Data\Base
{
	/**
	 * @var  \Fuel\Kernel\Parser\Parsable
	 */
	protected $parser;

	/**
	 * Load language file
	 *
	 * @param   string       $file
	 * @param   null|string  $language
	 * @return  Language
	 *
	 * @since  1.0.0
	 */
	public function load($file, $language = null)
	{
		$language = $language ?: $this->_app->env->language;
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
	 *
	 * @since  2.0.0
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
	 * @param   mixed   $default
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public function parse($string, array $values = array(), $default = null)
	{
		return ($string = $this->get($string)) ? $this->parser()->parse_string($string, $values) : __val($default);
	}
}