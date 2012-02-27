<?php

namespace Fuel\Kernel\Data;

class Config extends \Classes\Data\Base
{
	/**
	 * Load config file
	 *
	 * @param   string  $file
	 * @return  Config
	 */
	public function load($file)
	{
		$files = $this->_app->find_files('config', $file);
		foreach ($files as $file)
		{
			$array = require $file;
			$this->_data = array_merge($this->_data, $array);
		}
		return $this;
	}
}
