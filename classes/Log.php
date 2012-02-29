<?php

namespace Fuel\Kernel;

class Log
{
	/**
	 * @var  int  error level
	 *
	 * @since  1.0.0
	 */
	const L_ERROR = 1;

	/**
	 * @var  int  warning level
	 *
	 * @since  1.0.0
	 */
	const L_WARNING = 2;

	/**
	 * @var  int  debug level
	 *
	 * @since  1.0.0
	 */
	const L_DEBUG = 3;

	/**
	 * @var  int  info level
	 *
	 * @since  1.0.0
	 */
	const L_INFO = 4;

	/**
	 * @var  \Fuel\Kernel\Application\Base
	 */
	public $app;

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param  \Fuel\Kernel\Application\Base  $app
	 *
	 * @since  2.0.0
	 */
	public function _set_app(Application\Base $app)
	{
		$this->app = $app;
	}

	/**
	 * Logs a message with the Info Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 *
	 * @since  1.0.0
	 */
	public function info($msg, $method = null)
	{
		return static::write(self::L_INFO, $msg, $method);
	}

	/**
	 * Logs a message with the Debug Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 *
	 * @since  1.0.0
	 */
	public function debug($msg, $method = null)
	{
		return static::write(self::L_DEBUG, $msg, $method);
	}

	/**
	 * Logs a message with the Warning Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 *
	 * @since  1.0.0
	 */
	public function warning($msg, $method = null)
	{
		return static::write(self::L_WARNING, $msg, $method);
	}

	/**
	 * Logs a message with the Error Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 *
	 * @since  1.0.0
	 */
	public function error($msg, $method = null)
	{
		return static::write(self::L_ERROR, $msg, $method);
	}

	/**
	 * Write Log File
	 *
	 * Generally this function will be called using the global log_message() function
	 *
	 * @param   string  $level   the error level
	 * @param   string  $msg     the error message
	 * @param   string  $method  method or function that triggers this
	 * @return  bool
	 *
	 * @since  1.0.0
	 */
	public function write($level, $msg, $method = null)
	{
		if ($level > $this->app->config->get('log_threshold', 0))
		{
			return false;
		}
		$levels = array(
			1  => 'Error',
			2  => 'Warning',
			3  => 'Debug',
			4  => 'Info',
		);
		$level = isset($levels[$level]) ? $levels[$level] : $level;

		$filepath = $this->app->config->get('log_path').date('Y/m').'/';

		if ( ! is_dir($filepath))
		{
			$old = umask(0);

			mkdir($filepath, $this->app->config->get('file.chmod.folders', 0777), true);
			umask($old);
		}

		$filename = $filepath.date('d').'.php';

		$message  = '';

		if ( ! $exists = file_exists($filename))
		{
			$message .= "<"."?php defined('COREPATH') or exit('No direct script access allowed'); ?".">".PHP_EOL.PHP_EOL;
		}

		if ( ! $fp = @fopen($filename, 'a'))
		{
			return false;
		}

		$call = '';
		if ( ! empty($method))
		{
			$call .= $method;
		}

		$message .= $level.' '.(($level == 'info') ? ' -' : '-').' ';
		$message .= date($this->app->config->get('log_date_format', 'Y-m-d'));
		$message .= ' --> '.(empty($call) ? '' : $call.' - ').$msg.PHP_EOL;

		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);

		if ( ! $exists)
		{
			$old = umask(0);
			@chmod($filename, $this->app->config->get('file.chmod.files', 0666));
			umask($old);
		}

		return true;
	}
}
