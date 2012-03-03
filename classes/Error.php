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
	 * @var  array  types of error that are considered fatal
	 *
	 * @since  1.0.0
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
	 * @since  1.0.0
	 */
	public $non_fatal_cache = array();

	/**
	 * @var  \Fuel\Kernel\Application\Base  app that created this request
	 *
	 * @since  2.0.0
	 */
	public $app;

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
		$this->app = $app;
	}

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
		return $this->show_error($e);
	}

	/**
	 * Shows an error. It will stop script execution if the error code is not
	 * in the errors.continue_on whitelist.
	 *
	 * @param   \Exception  $e  the exception to show
	 * @return  void
	 *
	 * @since  1.0.0
	 */
	public function show_error(\Exception $e)
	{
		$continue_on  = $this->app->config->get('errors.continue_on', array());
		$fatal        = ! in_array($e->getCode(), $continue_on);

		if ($fatal)
		{
			$data['contents'] = ob_get_contents();
			while (ob_get_level() > 0)
			{
				ob_end_clean();
			}
			$ob_callback = $this->app->config->get('ob_callback', null);
			ob_start($ob_callback);
		}
		else
		{
			$this->non_fatal_cache[] = $e;
		}

		if ($this->app->env->is_cli)
		{
			$this->show_cli($e, $fatal);
		}
		elseif ($fatal)
		{
			if ( ! headers_sent())
			{
				$protocol = $this->app->env->input->server('SERVER_PROTOCOL')
					? $this->app->env->input->server('SERVER_PROTOCOL')
					: 'HTTP/1.1';
				header($protocol.' 500 Internal Server Error');
			}
			$this->show_fatal($e);
		}

		$this->show_non_fatal($e);
	}

	/**
	 * Show error on CLI
	 *
	 * @param   \Exception  $e
	 * @param   bool        $fatal
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function show_cli(\Exception $e, $fatal)
	{
		$cli = $this->app->get_object('Cli');
		$cli->write($cli->color((string) $e, 'red'));
		$fatal and exit(1);
		return;
	}

	/**
	 * Show non fatal error
	 *
	 * @param   \Exception  $e
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function show_non_fatal(\Exception $e)
	{
		echo '<pre style="background: white; padding: 10px; margin: 10px; border: 2px dashed #ff4500; color: #ff4500;
			font-weight: bold; font-size: 12px; font-family: Courier, sans-serif;">'.$e->getMessage().'</pre>';
	}

	/**
	 * Show fatal error
	 *
	 * @param   \Exception  $e
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function show_fatal(\Exception $e)
	{
		echo '<pre style="background: white; padding: 10px; margin: 10px; border: 2px dashed red; color: red;
			font-weight: bold; font-size: 12px; font-family: Courier, sans-serif;">'.$e.'</pre>';
		exit(1);
	}
}
