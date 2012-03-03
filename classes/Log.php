<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel;

/**
 * Logging class
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
class Log
{
	/**
	 * @var  int  no logging
	 *
	 * @since  2.0.0
	 */
	const L_NONE = 0;

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
	const L_NOTICE = 4;

	/**
	 * @var  int  info level
	 *
	 * @since  1.0.0
	 */
	const L_INFO = 8;

	/**
	 * @var  int  info level
	 *
	 * @since  1.0.0
	 */
	const L_DEPRECATED = 32;

	/**
	 * @var  int  show any errors within 16bits
	 *
	 * @since  2.0.0
	 */
	const L_ALL = 65535;

	/**
	 * @var  \Fuel\Kernel\Application\Base
	 *
	 * @since  2.0.0
	 */
	public $app;

	/**
	 * @var  array  levels and their textual meaning
	 *
	 * @since  2.0.0
	 */
	public $levels = array(
		self::L_ERROR       => 'Error',
		self::L_WARNING     => 'Warning',
		self::L_NOTICE      => 'Debug',
		self::L_INFO        => 'Info',
		self::L_DEPRECATED  => 'Deprecated',
	);

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
	 * Logs a message with the Deprecated Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 *
	 * @since  2.0.0
	 */
	public function deprecated($msg, $method = null)
	{
		return static::write(self::L_DEPRECATED, $msg, $method);
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
	 * Logs a message with the Notice Log Level
	 *
	 * @param   string  $msg     The log message
	 * @param   string  $method  The method that logged
	 * @return  bool    If it was successfully logged
	 *
	 * @since  2.0.0
	 */
	public function notice($msg, $method = null)
	{
		return static::write(self::L_NOTICE, $msg, $method);
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
	 * @param   int     $level   the error level
	 * @param   string  $msg     the error message
	 * @param   string  $method  method or function that triggers this
	 * @return  bool
	 *
	 * @since  1.0.0
	 */
	public function write($level, $msg, $method = null)
	{
		// Check if the given level is valid and part of the bitmask for reporting
		if ( ! is_int($level) or ($this->app->config->get('log.flags', 0) & $level) !== $level)
		{
			return false;
		}

		// Translate the level when possible
		$level = isset($this->levels[$level]) ? $this->levels[$level] : $level;

		// Fetch the path and check if it exists
		$filepath = $this->app->config->get('log.path', $this->app->loader->path().'resources/logs/').date('Y/m').'/';

		// Attempt to create the directory if it doesn't exist
		if ( ! is_dir($filepath))
		{
			$old = umask(0);
			mkdir($filepath, $this->app->config->get('file.chmod.folders', 0777), true);
			umask($old);
		}

		// Define the filename to be used
		$filename = $filepath.date('d').'.php';
		$exists = ! file_exists($filename);

		// Open the file or fail when not possible
		if ( ! $fp = @fopen($filename, 'a'))
		{
			return false;
		}

		// Build the message
		$message  = $level.' - ';
		$message .= date($this->app->config->get('log.date_format', 'Y-m-d'));
		$message .= ' - '.$msg.(empty($method) ? '' : ' --> '.$method).PHP_EOL;

		// Write the file
		flock($fp, LOCK_EX);
		fwrite($fp, $message);
		flock($fp, LOCK_UN);
		fclose($fp);

		// Chmod the file if it was just created
		if ($exists)
		{
			$old = umask(0);
			@chmod($filename, $this->app->config->get('file.chmod.files', 0666));
			umask($old);
		}

		return true;
	}
}
