<?php

namespace Fuel\Kernel\View;

interface Viewable
{
	/**
	 * Setter: One must be able to set any variable upon the Viewable
	 *
	 * @param  $name
	 * @param  $value
	 */
	public function __set($name, $value);

	/**
	 * Getter: One must be able to get variables from the Viewable
	 *
	 * @param  $name
	 */
	public function & __get($name);

	/**
	 * The Viewable must be able to turn into a string
	 */
	public function __toString();
}
