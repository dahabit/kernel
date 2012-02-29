<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel;

/**
 * Error
 *
 * Deals with showing Exceptions and PHP errors.
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
class Error
{
	/**
	 * @var  array  add names for the error levels
	 *
	 * @since  2.0.0
	 */
	public $levels = array(
		0                  => 'Error',
		E_ERROR            => 'Error',
		E_WARNING          => 'Warning',
		E_PARSE            => 'Parsing Error',
		E_NOTICE           => 'Notice',
		E_CORE_ERROR       => 'Core Error',
		E_CORE_WARNING     => 'Core Warning',
		E_COMPILE_ERROR    => 'Compile Error',
		E_COMPILE_WARNING  => 'Compile Warning',
		E_USER_ERROR       => 'User Error',
		E_USER_WARNING     => 'User Warning',
		E_USER_NOTICE      => 'User Notice',
		E_STRICT           => 'Runtime Notice'
	);

	/**
	 * @var  array  types of error that are considered fatal
	 *
	 * @since  2.0.0
	 */
	public $fatal_levels = array(
		E_PARSE,
		E_ERROR,
		E_USER_ERROR,
		E_COMPILE_ERROR
	);

	/**
	 * @var  array  Non fatal errors thrown before a fatal error occurred
	 *
	 * @since  2.0.0
	 */
	public $non_fatal_cache = array();

	/**
	 * PHP Exception handler
	 *
	 * @param   \Exception  $e  the exception
	 * @return  bool
	 *
	 * @since  2.0.0
	 */
	public function handle(\Exception $e)
	{
		if (method_exists($e, 'handle'))
		{
			return $e->handle();
		}
		$this->show_error($e);
	}

	/**
	 * Shows an error. It will stop script execution if the error code is not
	 * in the errors.continue_on whitelist.
	 *
	 * @param   \Exception  $e  the exception to show
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function show_error(\Exception $e)
	{
		$continue_on = _app() ? _app('config')->get('errors.continue_on', array()) : array();
		$fatal       = ! in_array($e->getCode(), $continue_on);
		$data        = $this->prepare_exception($e, $fatal);

		if ($fatal)
		{
			$data['contents'] = ob_get_contents();
			while (ob_get_level() > 0)
			{
				ob_end_clean();
			}
			$ob_callback = _app() ? _app('config')->get('ob_callback', null) : null;
			ob_start($ob_callback);
		}
		else
		{
			$this->non_fatal_cache[] = $data;
		}

		if (_env('is_cli'))
		{
			$cli = _app() ? _app()->get_object('Cli') : _env()->get_object('Cli');
			$cli->write($cli->color($data['severity'].' - '.$data['message'].' in '.$data['filepath'].' on line '.$data['error_line'], 'red'));
			$fatal and exit(1);
			return;
		}

		if ($fatal)
		{
			if ( ! headers_sent())
			{
				$protocol = _env('input')->server('SERVER_PROTOCOL')
					? _env('input')->server('SERVER_PROTOCOL')
					: 'HTTP/1.1';
				header($protocol.' 500 Internal Server Error');
			}

			$data['non_fatal'] = $this->non_fatal_cache;

			$view_fatal = _app() ? _app('config')->get('errors.view_fatal') : '';
			if ($view_fatal)
			{
				exit(_forge('View', $view_fatal, $data, false));
			}
			else
			{
				exit($data['severity'].' - '.$data['message'].' in '.$data['filepath'].' on line '.$data['error_line']);
			}
		}

		try
		{
			echo _forge('View', _env('config')->get('errors.view_error'), $data, false);
		}
		catch (\Exception $e)
		{
			echo $e->getMessage().'<br />';
		}
	}

	/**
	 * Prepares Exception data for passage to the Viewable
	 *
	 * @param   \Exception  $e
	 * @param   bool        $fatal
	 * @return  array
	 *
	 * @since  2.0.0
	 */
	protected function prepare_exception(\Exception $e, $fatal = true)
	{
		// Convert exception to data array for error View
		$data = array();
		$data['type']        = get_class($e);
		$data['severity']    = $e->getCode();
		$data['message']     = $e->getMessage();
		$data['filepath']    = $e->getFile();
		$data['error_line']  = $e->getLine();
		$data['backtrace']   = $e->getTrace();

		// Translate severity int to string
		$data['severity'] = ( ! isset($this->levels[$data['severity']]))
			? $data['severity']
			: $this->levels[$data['severity']];

		// Unset unnecessary backtrace entries
		foreach ($data['backtrace'] as $key => $trace)
		{
			if ( ! isset($trace['file']))
			{
				unset($data['backtrace'][$key]);
			}
			elseif ($trace['file'] == __FILE__)
			{
				unset($data['backtrace'][$key]);
			}
		}

		// @todo implement the commented out lines
		// $data['debug_lines'] = \Debug::file_lines($data['filepath'], $data['error_line'], $fatal);
		$data['orig_filepath'] = $data['filepath'];
		// $data['filepath'] = \Fuel::clean_path($data['filepath']);
		$data['filepath'] = str_replace("\\", "/", $data['filepath']);

		return $data;
	}
}
