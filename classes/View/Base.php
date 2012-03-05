<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\View;
use Fuel\Kernel\Application;
use Fuel\Kernel\Parser;
use Fuel\Kernel\Security;

/**
 * Base View class
 *
 * Default Fuel View implementation.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
class Base implements Viewable
{
	/**
	 * @var  null|string  path to the View file
	 *
	 * @since  1.0.0
	 */
	protected $_path;

	/**
	 * @var  null|string  raw template to use as View
	 *
	 * @since  2.0.0
	 */
	protected $_template;

	/**
	 * @var  array  data to be passed to the view
	 *
	 * @since  1.0.0
	 */
	protected $_data = array();

	/**
	 * @var  bool|string  name after 'Security_String:' for the DiC, false to disable
	 *
	 * @since  1.0.0
	 */
	protected $_filter = true;

	/**
	 * @var  array  filters for individual data keys
	 *
	 * @since  1.0.0
	 */
	protected $_data_filters = array();

	/**
	 * @var  \Fuel\Kernel\Parser\Parsable
	 *
	 * @since  2.0.0
	 */
	protected $_parser = 'Parser:View';

	/**
	 * @var  \Fuel\Kernel\Application\Base
	 *
	 * @since  2.0.0
	 */
	protected $_app;

	/**
	 * @var  \Fuel\Kernel\Request\Base
	 *
	 * @since  1.1.0
	 */
	protected $_context;

	/**
	 * Constructor
	 *
	 * @param  null|string  $file
	 * @param  array        $data
	 * @param  null|string|\Fuel\Kernel\Parser\Parsable       $parser
	 * @param  bool|string|\Fuel\Kernel\Security\String\Base  $filter
	 *
	 * @since  1.0.0
	 */
	public function __construct($file = null, array $data = array(), $parser = null, $filter = null)
	{
		$this->_path    = $file;
		$this->_data    = $data;
		$this->_filter  = $filter;

		// Allow overwriting default Parsable
		if ( ! is_null($parser))
		{
			// A string Parser classname must be prefixed with 'Parser:'
			if (is_string($parser) and ! substr($parser, 0, 7) == 'Parser:')
			{
				$parser = 'Parser:'.$parser;
			}
			$this->_parser = $parser;
		}
	}

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param   \Fuel\Kernel\Application\Base  $app
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function _set_app(Application\Base $app)
	{
		$this->_app      = $app;
		$this->_context  = $app->active_request();

		// Allow for the parser to already have been set as a string or Parsable object
		if ( ! $this->_parser instanceof Parser\Parsable)
		{
			$this->_parser = $app->get_object($this->_parser ?: 'Parser:View');
		}

		// Fetch the full path from the Application
		$this->_path and $this->set_filename($this->_path);

		// Set the filter object
		is_null($this->_filter) and $this->_filter = $app->config->get('security.output_filter', true);
		if ($this->_filter === true)
		{
			$this->_filter = $app->security->string();
		}
		elseif (is_string($this->_filter))
		{
			$this->_filter = $app->get_object('Security_String:'.$this->_filter);
		}
		// Check if filter has become a valid filter object
		if ( ! $this->_filter instanceof Security\String\Base and $this->_filter !== false)
		{
			throw new \RuntimeException('Filter set on the View is not a valid string filter.');
		}
	}

	/**
	 * Change the View filename
	 *
	 * @param   string  $file
	 * @return  Base
	 *
	 * @since  1.0.0
	 */
	public function set_filename($file)
	{
		$this->_path      = $this->_app->find_file('views', $file.'.'.$this->_parser->extension());
		$this->_template  = null;

		if (empty($this->_path) or ! file_exists($this->_path))
		{
			throw new \OutOfBoundsException('Given path could not be found by set_filename(): '.$file);
		}

		return $this;
	}

	/**
	 * Change the View template string
	 *
	 * @param   string  $template
	 * @return  Base
	 *
	 * @since  2.0.0
	 */
	public function set_template($template)
	{
		$this->_path      = null;
		$this->_template  = $template;
		return $this;
	}

	/**
	 * Set variable on View
	 *
	 * @param   string  $name
	 * @param   mixed   $value
	 * @param   mixed   $filter
	 * @return  Base
	 * @throws  \LogicException
	 *
	 * @since  1.0.0
	 */
	public function set($name, $value, $filter = null)
	{
		if (strlen($name) > 2 and $name[0] == '_' and $name[1] != '_')
		{
			throw new \LogicException('Properties with a single underscore prefix are preserved for Viewable usage.');
		}

		$this->_data[$name] = __val($value);
		! is_null($filter) and $this->_data_filters[$name] = $filter;

		return $this;
	}

	/**
	 * Retrieves all the data, both local and global.  It filters the data if
	 * necessary.
	 *
	 * @return  array
	 */
	protected function get_data()
	{
		$data = array();

		// First add the view data
		foreach ($this->_data as $key => $value)
		{
			$filter = isset($this->_data_filters[$key]) ? $this->_data_filters[$key] : $this->_filter;
			$data[$key] = $filter ? $filter->clean($value) : $value;
		}

		// Then add environment data, this is always unfiltered
		$data += $this->_app->env->get_var();

		return $data;
	}

	/**
	 * Render the View
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public function render()
	{
		// Check if app is active
		$application_activated = false;
		if ($this->_app->env->active_application() !== $this->_app)
		{
			$this->_app->activate();
			$application_activated = true;
		}

		// Then make sure the Request that created this is active
		$request_activated = false;
		if ($this->_app->active_request() !== $this->_context)
		{
			$this->_context->activate();
			$request_activated = true;
		}

		// Render the View
		$view = $this->parse();

		// When Request/Application was activated, deactivate now we're done
		$request_activated and $this->_context->deactivate();
		$application_activated and $this->_app->deactivate();

		return $view;
	}

	protected function parse()
	{
		return $this->_path
			? $this->_parser->parse_file($this->_path, $this->get_data())
			: $this->_parser->parse_string($this->_template, $this->get_data());
	}

	/**
	 * Magic setter
	 *
	 * @param   string  $name
	 * @param   mixed   $value
	 * @throws  \LogicException
	 *
	 * @since  1.0.0
	 */
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}

	/**
	 * Magic getter
	 *
	 * @param   string  $name
	 * @return  mixed
	 * @throws  \OutOfBoundsException
	 *
	 * @since  1.0.0
	 */
	public function & __get($name)
	{
		if ( ! isset($this->_data[$name]))
		{
			throw new \OutOfBoundsException('Property "'.$name.'" not set upon Viewable.');
		}

		return $this->_data[$name];
	}

	/**
	 * Turns the presenter into a string
	 *
	 * @return  string
	 *
	 * @since  1.0.0
	 */
	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch (\Exception $e)
		{
			echo '<pre>'.$e.'</pre>';
		}
	}
}
