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
 * Config class
 *
 * Configuration container.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
class Config extends \Classes\Data\Base
{
	/**
	 * Load config file
	 *
	 * @param   string  $file
	 * @return  Config
	 *
	 * @since  1.0.0
	 */
	public function load($file)
	{
		// Find the main files
		$files = $this->_app->find_files('config', $file);
		foreach ($files as $file)
		{
			$array = require $file;
			$this->_data = array_merge($this->_data, $array);
		}

		// Find optional overwrites
		$this->_app->find_files('config/'._env('name'), $file);
		foreach ($files as $file)
		{
			$array = require $file;
			$this->_data = array_merge($this->_data, $array);
		}

		return $this;
	}
}
