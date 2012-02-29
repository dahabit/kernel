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
 * Environment
 *
 * Sets up the environment for PHP and Fuel.
 *
 * @package  Fuel\Kernel
 */
class Environment
{
	/**
	 * @constant  string  version identifier
	 */
	const VERSION = '2.0-alpha';

	/**
	 * @var  Environment  instance
	 */
	public static $instance;

	/**
	 * Singleton may be evil but to allow multiple instances would be wrong
	 *
	 * @return  Environment
	 */
	public static function instance()
	{
		if (is_null(static::$instance))
		{
			static::$instance = new static;
		}

		return static::$instance;
	}

	/**
	 * @var  array  environment names with closures as values
	 */
	protected $environments = array();

	/**
	 * @var  string  name of the current environment
	 */
	public $name = 'development';

	/**
	 * @var  string|null  optional overwrite for system environment setting
	 */
	public $locale = null;

	/**
	 * @var  string  language identifier
	 */
	public $language = 'en';

	/**
	 * @var  string|null  timezone name for php.net/timezones
	 */
	public $timezone = 'UTC';

	/**
	 * @var  bool  whether or not usage of MBSTRING extension is enabled
	 */
	public $mbstring = true;

	/**
	 * @var  string|null  character encoding
	 */
	public $encoding = 'UTF-8';

	/**
	 * @var  bool  whether this is run through the command line
	 */
	public $is_cli = false;

	/**
	 * @var  bool  Readline is an extension for PHP that makes interactive with PHP much more bash-like
	 */
	public $readline_support = false;

	/**
	 * @var  array  appnames and their classnames
	 */
	protected $apps = array();

	/**
	 * @var  array  paths registered in the global environment
	 */
	protected $paths = array();

	/**
	 * @var  string  base url
	 */
	public $base_url;

	/**
	 * @var  string
	 */
	public $index_file;

	/**
	 * @var  Input  the input container
	 */
	public $input;

	/**
	 * @var  Loader  the loader container
	 */
	public $loader;

	/**
	 * @var  DiC\Base
	 */
	public $dic;

	/**
	 * @var  Application\Base;
	 */
	public $active_app;

	/**
	 * @var  array  container for environment variables
	 */
	protected $vars = array();

	/**
	 * Constructor
	 *
	 * @return  void
	 */
	public function __construct()
	{
		$this->vars['init_time'] = microtime(true);
		$this->vars['init_mem']  = memory_get_usage();
	}

	/**
	 * Allows the overwriting of the environment settings, should only be run once
	 *
	 * @param   array  $config
	 * @return  Environment  to allow method chaining
	 */
	public function init(array $config)
	{
		// Prevent double init
		static $init = false;
		if ($init)
		{
			trigger_error('Environment config can\'t be initiated more than once.', E_USER_ERROR);
		}

		// Fuel path must be given
		if ( ! isset($config['path']) and ! isset($config['paths']['fuel']))
		{
			trigger_error('The path to the Fuel packages directory must be provided to Environment.', E_USER_ERROR);
		}

		// Rewrite single paths into multiple
		if (isset($config['path']))
		{
			$config['paths']['fuel'] = $config['path'];
			unset($config['path']);
		}

		// Load environments
		$this->environments = require trim($config['paths']['fuel'], '\\/').'/environments.php';

		// Run default environment
		$env = array();
		if (isset($this->environments['__default']))
		{
			$env = (array) call_user_func($this->environments['__default']);
		}

		// Run specific environment config when given
		$config['name'] = isset($config['name']) ? $config['name'] : 'development';
		if (isset($this->environments[$config['name']]))
		{
			$env = array_merge($env, (array) call_user_func($this->environments[$config['name']]));
		}

		// Merge given config with environment returns
		$env = array_merge($env, $config);

		// Separate out the packages for later usage (after loader init)
		$packages = isset($env['packages']) ? (array) $env['packages'] : array();

		foreach ($env as $key => $val)
		{
			if (property_exists($this, $key))
			{
				$this->{$key} = $val;
			}
		}

		// Load the system helpers
		require_once (isset($env['helpers']) ? $env['helpers'] : __DIR__.'/../helpers.php');

		// Set the environment DiC when not yet set
		if ( ! $this->dic instanceof DiC\Dependable)
		{
			! class_exists('Fuel\\Kernel\\DiC\\Dependable', false) and require __DIR__ . '/DiC/Dependable.php';
			! class_exists('Fuel\\Kernel\\DiC\\Base', false) and require __DIR__ . '/DiC/Base.php';
			$this->dic = new DiC\Base();
		}

		// Set the class & fileloader
		$this->set_loader($this->loader);

		// Load the input container if not yet set
		( ! $this->input instanceof Input) and $this->input = new Input();

		// Configure the localization options for PHP
		$this->set_locale($this->locale);
		$this->set_timezone($this->timezone);

		// Detects and configures the PHP Environment
		$this->php_env();

		// Load additional 'Core' packages
		foreach ($packages as $pkg)
		{
			$this->loader->load_package($pkg, Loader::TYPE_CORE);
		}

		$init = true;

		return $this;
	}

	/**
	 * Detects and configures the PHP Environment
	 *
	 * @return  void
	 */
	protected function php_env()
	{
		$this->is_cli = (bool) defined('STDIN');
		if ($this->is_cli)
		{
			$this->readline_support = extension_loaded('readline');
		}

		// Detect the base URL when not given
		if (is_null($this->base_url) and ! $this->is_cli)
		{
			$this->base_url = $this->detect_base_url();
		}

		// When mbstring setting was not given default to availability
		if ( ! isset($config['mbstring']))
		{
			$this->mbstring = function_exists('mb_get_info');
		}
		$this->set_encoding($this->encoding);

		// Setup Error & Exception handlers
		register_shutdown_function(function ()
		{
			$error = error_get_last();

			if ( ! $error)
			{
				return;
			}

			$error = new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
			if ($handler = _app('error'))
			{
				return $handler->handle($error);
			}
			exit(_env('is_cli') ? $error : nl2br($error));
		});
		set_error_handler(function ($severity, $message, $filepath, $line)
		{
			$error = new \ErrorException($message, $severity, 0, $filepath, $line);
			if ($handler = _app('error'))
			{
				return $handler->handle($error);
			}
			exit(nl2br($error));
		});
		set_exception_handler(function (\Exception $e)
		{
			if ($handler = _app('error'))
			{
				return $handler->handle($e);
			}

			echo '<h1>Error code: #'.$e->getCode().'</h1>';
			echo '<p>'.$e->getMessage().'</p>';
			echo '<p>Occorred in file "'.$e->getFile().'" on line "'.$e->getLine().'".</p>';
			exit(1);
		});
	}

	/**
	 * Generates a base url.
	 *
	 * @return  string  the base url
	 */
	public function detect_base_url()
	{
		$base_url = '';
		if ($this->input->server('http_host'))
		{
			$base_url .= $this->input->protocol().'://'.$this->input->server('http_host');
		}
		if ($this->input->server('script_name'))
		{
			$base_url .= str_replace('\\', '/', dirname($this->input->server('script_name')));

			// Add a slash if it is missing
			$base_url = rtrim($base_url, '/').'/';
		}
		return $base_url;
	}

	/**
	 * Set the locale
	 *
	 * @param   string|null  $locale  locale name (OS dependent)
	 * @return  Environment  to allow method chaining
	 */
	public function set_locale($locale)
	{
		$this->locale = $locale;
		$this->locale and setlocale(LC_ALL, $this->locale);
		return $this;
	}

	/**
	 * Set the timezone
	 *
	 * @param   string|null  $timezone  timezone name (http://php.net/timezones)
	 * @return  Environment  to allow method chaining
	 */
	public function set_timezone($timezone)
	{
		$this->timezone = $timezone;
		$this->timezone and date_default_timezone_set($this->timezone);
		return $this;
	}

	/**
	 * Set the character encoding (only when mbstring is enabled)
	 *
	 * @param   string|null  $encoding  encoding name
	 * @return  Environment  to allow method chaining
	 */
	public function set_encoding($encoding)
	{
		$this->encoding = $encoding;
		$this->encoding and mb_internal_encoding($this->encoding);
		return $this;
	}

	/**
	 * Set the file & classloader
	 *
	 * @param   string|null|Loader  $loader  either a loader instance or its classname
	 * @return  Environment  to allow method chaining
	 */
	public function set_loader($loader)
	{
		// Get the loader from the given arg
		if (is_string($loader))
		{
			$loader = new $loader();
		}
		elseif (empty($loader))
		{
			! class_exists('Fuel\\Kernel\\Loader', false) and require __DIR__ . '/Loader.php';
			! class_exists('Fuel\\Kernel\\Loader\\Loadable', false) and require __DIR__ . '/Loader/Loadable.php';
			! class_exists('Fuel\\Kernel\\Loader\\Package', false) and require __DIR__ . '/Loader/Package.php';
			$loader = new Loader();
		}

		// Set the loader as a property and register it with PHP
		$this->loader = $loader;
		spl_autoload_register(array($this->loader, 'load_class'), true, true);

		// Add the Kernel as a core package
		$loader->load_package('fuel/kernel', Loader::TYPE_CORE);

		return $this;
	}

	/**
	 * Fetch the Application classname
	 *
	 * @param   string  $appname
	 * @return  string
	 * @throws  \OutOfBoundsException
	 */
	public function app_class($appname)
	{
		if ( ! isset($this->apps[$appname]))
		{
			throw new \OutOfBoundsException('Unknown Appname: '.$appname);
		}

		return $this->apps[$appname];
	}

	/**
	 * Register a new app classname
	 *
	 * @param   string  $appname    Given name for an application
	 * @param   string  $classname  Classname for the application
	 * @return  Environment
	 */
	public function register_app($appname, $classname)
	{
		$this->apps[$appname] = $classname;
		return $this;
	}

	/**
	 * Fetch the full path for a given pathname
	 *
	 * @param   string  $name
	 * @return  string
	 * @throws  \OutOfBoundsException
	 */
	public function path($name)
	{
		if ( ! isset($this->paths[$name]))
		{
			throw new \OutOfBoundsException('Unknown path requested: '.$name);
		}

		return $this->paths[$name];
	}

	/**
	 * Register a new named path
	 *
	 * @param   string       $name       name for the path
	 * @param   string       $path       the full path
	 * @param   bool         $overwrite  whether or not overwriting existing name is allowed
	 * @return  Environment  to allow method chaining
	 * @throws  \OutOfBoundsException
	 */
	public function add_path($name, $path, $overwrite = false)
	{
		if ( ! $overwrite and isset($this->paths[$name]))
		{
			throw new \OutOfBoundsException('Already a path registered for name: '.$name);
		}

		$this->paths[$name] = trim($path, '/\\').'/';
		return $this;
	}

	/**
	 * Set a global variable
	 *
	 * @param   string  $name
	 * @param   mixed   $value
	 * @return  Environment  to allow method chaining
	 */
	public function set_var($name, $value)
	{
		$this->vars[$name] = $value;
		return $this;
	}

	/**
	 * Get a global variable
	 *
	 * @param   string  $name
	 * @param   mixed   $default  value to return when name is unknown
	 * @return  mixed
	 */
	public function get_var($name, $default = null)
	{
		if ( ! isset($this->vars[$name]))
		{
			return $default;
		}
		return $this->vars[$name];
	}

	/**
	 * Fetch the time that has elapsed since Fuel Kernel init
	 *
	 * @return  float
	 */
	public function time_elapsed()
	{
		return microtime(true) - $this->get_var('init_time');
	}

	/**
	 * Fetch the mem usage change since Fuel Kernel init
	 *
	 * @param   bool  $peak  whether to report the peak usage instead of the current
	 * @return  float
	 */
	public function mem_usage($peak = false)
	{
		$usage = $peak ? memory_get_peak_usage() : memory_get_usage();
		return $usage - $this->get_var('init_mem');
	}

	/**
	 * Sets the current active Application
	 *
	 * @param   Application\Base  $app
	 * @return  Environment
	 */
	public function set_active_app($app)
	{
		$this->active_app = $app;
		return $this;
	}

	/**
	 * Fetches the current active Application
	 *
	 * @return  Application\Base
	 */
	public function active_app()
	{
		return $this->active_app;
	}

	/**
	 * Translates a classname to the one set in the DiC classes property
	 *
	 * @param   string  $class
	 * @return  string
	 */
	public function get_class($class)
	{
		return $this->dic->get_class($class);
	}

	/**
	 * Forges a new object for the given class, supporting DI replacement
	 *
	 * @param   string  $class
	 * @return  object
	 */
	public function forge($class)
	{
		return call_user_func_array(array($this->dic, 'forge'), func_get_args());
	}

	/**
	 * Fetch an instance from the DiC
	 *
	 * @param   string  $class
	 * @param   string  $name
	 * @return  object
	 * @throws  \RuntimeException
	 */
	public function get_object($class, $name = null)
	{
		return $this->dic->get_object($class, $name);
	}
}
