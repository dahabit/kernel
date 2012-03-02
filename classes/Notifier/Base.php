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
use Fuel\Kernel\Application;

/**
 * Notifiable Interface
 *
 * Notifiable class instances can be notified to observe events and act upon them.
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
class Base
{
	/**
	 * @var  array  contains arrays with [0] observers and [1] the events that trigger them
	 */
	protected $observers = array();

	/**
	 * @var  array  observed events indexed by microtime timestamp
	 */
	protected $observed = array();

	/**
	 * Constructor
	 *
	 * @param  array  $observers
	 */
	public function __construct(array $observers = array())
	{
		foreach ($observers as $name => $observer)
		{
			! is_array($observer) and $observer = array($observer);
			call_user_func(
				array($this, 'register'),
				$name, $observer[0], isset($observer[1]) ? $observer[1] : array()
			);
		}
	}

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
	public function notify($event, $source = null, $method = '')
	{
		$this->observed[strval(microtime(true))] = $event;

		// Walk through all registered observers
		foreach ($this->observers as $observer)
		{
			// Notify when current event is registered for the observer or always when no events were specified
			if (empty($observer[1]) or in_array($event, $observer[1]))
			{
				call_user_func($observer[0], $event, $source, $method);
			}
		}
	}

	/**
	 * Add an event observer
	 *
	 * @param   string    $name
	 * @param   callback  $callback
	 * @param   array     $events  specific events to observe
	 * @return  Notifiable
	 *
	 * @since  2.0.0
	 */
	public function register($name, $callback, $events = array())
	{
		if ( ! is_callable($callback))
		{
			throw new \InvalidArgumentException('Cannot register given observer, must be callable.');
		}
		$events = $events ? (array) $events : array();

		// Add the Observer
		$this->observers[$name] = array($callback, $events);

		return $this;
	}

	/**
	 * Remove an event observer
	 *
	 * @param   string  $name
	 * @return  Notifiable
	 *
	 * @since  2.0.0
	 */
	public function unregister($name)
	{
		unset($this->observers[$name]);
		return $this;
	}

	/**
	 * Return array of observed events indexed by microtime timestamp
	 *
	 * @return  array
	 */
	public function observed()
	{
		return $this->observed;
	}

	/**
	 * Generates array of registered observers per event
	 *
	 * @return  array
	 */
	public function registered()
	{
		$return = array();
		// Walk through all observers
		foreach ($this->observers as $name => $observer)
		{
			// Walk through all events registered for observer or give __all as the only key
			$events = $observer[1] ?: array('__all');
			foreach ($events as $event)
			{
				! isset($return[$event]) and $return[$event] = array();
				$return[$event][] = $name;
			}
		}
		return $return;
	}
}
