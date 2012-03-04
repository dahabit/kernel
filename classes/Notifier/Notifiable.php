<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Notifier;

/**
 * Notifiable Interface
 *
 * Notifiable class instances can be notified to observe events and act upon them.
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
interface Notifiable
{
	/**
	 * Notify the Notifiable of an event
	 *
	 * @param   string       $event
	 * @param   null|object  $source
	 * @param   string       $method  expects __METHOD__ as input
	 * @return  Notifiable
	 *
	 * @since  2.0.0
	 */
	public function notify($event, $source = null, $method = '');

	/**
	 * Add an event observer
	 *
	 * @param   callback  $callback
	 * @param   array     $events  specific events to observe
	 * @return  Notifiable
	 *
	 * @since  2.0.0
	 */
	public function register($callback, array $events = array());

	/**
	 * Remove an event observer
	 *
	 * @param   callback  $callback
	 * @return  Notifiable
	 *
	 * @since  2.0.0
	 */
	public function unregister($callback);

	/**
	 * Return array of observed events indexed by microtime timestamp
	 *
	 * @return  array
	 */
	public function observed();
}
