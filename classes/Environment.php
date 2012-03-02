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
 *
 * @since  2.0.0
 */
class Environment
{
	/**
	 * @constant  string  version identifier
	 *
	 * @since  2.0.0
	 */
	const VERSION = '2.0-alpha';

	/**
	 * @var  Environment  instance
	 *
	 * @since  2.0.0
	 */
	public static $instance;

	/**
	 * Singleton may be evil but to allow multiple instances would be wrong
	 *
	 * @return  Environment
	 *
	 * @since  2.0.0
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
	 * @var  bool  whether init() has been run
	 *
	 * @since  2.0.0
	 */
	protected $initialized = false;

	/**
	 * @var  string  name of the current environment
	 *
	 * @since  2.0.0
	 */
	public $name = 'development';

	/**
	 * @var  string|null  optional overwrite for system environment setting
	 *
	 * @since  2.0.0
	 */
	public $locale = null;

	/**
	 * @var  string  language identifier
	 *
	 * @since  2.0.0
	 */
	public $language = 'en';

	/**
	 * @var  string|null  timezone name for php.net/timezones
	 *
	 * @since  2.0.0
	 */
	public $timezone = 'UTC';

	/**
	 * @var  bool  whether or not usage of MBSTRING extension is enabled
	 *
	 * @since  2.0.0
	 */
	public $mbstring = true;

	/**
	 * @var  string|null  character encoding
	 *
	 * @since  2.0.0
	 */
	public $encoding = 'UTF-8';

	/**
	 * @var  bool  whether this is run through the command line
	 *
	 * @since  2.0.0
	 */
	public $is_cli = false;

	/**
	 * @var  bool  Readline is an extension for PHP that makes interactive with PHP much more bash-like
	 *
	 * @since  2.0.0
	 */
	public $readline_support = false;

	/**
	 * @var  array  appnames and their classnames
	 *
	 * @since  2.0.0
	 */
	protected $apps = array();

	/**
	 * @var  array  paths registered in the global environment
	 *
	 * @since  2.0.0
	 */
	protected $paths = array();

	/**
	 * @var  string  base url
	 *
	 * @since  2.0.0
	 */
	public $base_url;

	/**
	 * @var  string
	 *
	 * @since  2.0.0
	 */
	public $index_file;

	/**
	 * @var  Input  the input container
	 *
	 * @since  2.0.0
	 */
	public $input;

	/**
	 * @var  Loader  the loader container
	 *
	 * @since  2.0.0
	 */
	public $loader;

	/**
	 * @var  DiC\Base
	 *
	 * @since  2.0.0
	 */
	public $dic;

	/**
	 * @var  Application\Base
	 *
	 * @since  2.0.0
	 */
	public $active_app;

	/**
	 * @var  array  container for environment variables
	 *
	 * @since  2.0.0
	 */
	protected $vars = array();

	/**
	 * Constructor
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
	 */
	public function init(array $config)
	{
		if ($this->initialized)
		{
			throw new \RuntimeException('Environment config shouldn\'t be initiated more than once.', E_USER_ERROR);
		}

		// Fuel path must be given
		if ( ! isset($config['path']) and ! isset($config['paths']['fuel']))
		{
			throw new \RuntimeException('The path to the Fuel packages directory must be provided to Environment.', E_USER_ERROR);
		}

		// Rewrite single paths into multiple
		if (isset($config['path']))
		{
			$config['paths']['fuel'] = $config['path'];
			unset($config['path']);
		}

		// Set (if array) or load (when empty/string) environments
		$environments = isset($config['environments'])
			? $config['environments']
			: rtrim($config['paths']['fuel'], '\\/').'/environments.php';
		is_string($environments)
			and $environments = require $environments;
		unset($config['environments']);

		// Run default environment
		$finish_callbacks = array();
		if (isset($environments['__default']))
		{
			$finish_callbacks[] = call_user_func($environments['__default'], $this);
		}

		// Run specific environment config when given
		$config['name'] = isset($config['name']) ? $config['name'] : 'development';
		if (isset($environments[$config['name']]))
		{
			$finish_callbacks[] = call_user_func($environments[$config['name']], $this);
		}

		// Separate out the packages for later usage (after loader init)
		$packages = isset($config['packages']) ? (array) $config['packages'] : array();
		unset($config['packages']);

		foreach ($config as $key => $val)
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
			! interface_exists('Fuel\\Kernel\\DiC\\Dependable', false)
				and require __DIR__ . '/DiC/Dependable.php';
			! class_exists('Fuel\\Kernel\\DiC\\Base', false)
				and require __DIR__ . '/DiC/Base.php';
			$this->dic = new DiC\Base();
		}

		// Set the class & fileloader
		$this->set_loader($this->loader);

		// Load the input container if not yet set
		( ! $this->input instanceof Input) and $this->input = new Input();
		$this->input->_set_env($this);

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

		// Run callbacks to finish up
		foreach ($finish_callbacks as $cb)
		{
			is_callable($cb) and call_user_func($cb, $this);
		}

		$this->initialized = true;

		return $this;
	}

	/**
	 * Detects and configures the PHP Environment
	 *
	 * @return  void
	 *
	 * @since  2.0.0
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
		$env = $this;
		register_shutdown_function(function () use ($env)
		{
			$error = error_get_last();

			if ( ! $error)
			{
				return true;
			}

			$error = new \ErrorException($error['message'], $error['type'], 0, $error['file'], $error['line']);
			if (($app = $env->active_application()) and $handler = $app->error)
			{
				return $handler->handle($error);
			}
			exit($env->is_cli ? $error : nl2br($error));
		});
		set_error_handler(function ($severity, $message, $filepath, $line) use ($env)
		{
			$error = new \ErrorException($message, $severity, 0, $filepath, $line);
			if (($app = $env->active_application()) and $handler = $app->error)
			{
				return $handler->handle($error);
			}
			exit($env->is_cli ? $error : nl2br($error));
		});
		set_exception_handler(function (\Exception $e) use ($env)
		{
			if (($app = $env->active_application()) and $handler = $app->error)
			{
				return $handler->handle($e);
			}

			! $env->is_cli and print('<pre>');
			echo $e;
			! $env->is_cli and print('</pre>');
			exit($e->getCode() ?: 1);
		});
	}

	/**
	 * Generates a base url.
	 *
	 * @return  string  the base url
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
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
		$this->loader->_set_env($this);
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
	 *
	 * @since  2.0.0
	 */
	public function application_class($appname)
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
	 *
	 * @since  2.0.0
	 */
	public function register_application($appname, $classname)
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
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
	 */
	public function add_path($name, $path, $overwrite = false)
	{
		if ( ! $overwrite and isset($this->paths[$name]))
		{
			throw new \OutOfBoundsException('Already a path registered for name: '.$name);
		}

		$this->paths[$name] = rtrim($path, '/\\').'/';
		return $this;
	}

	/**
	 * Set a global variable
	 *
	 * @param   string  $name
	 * @param   mixed   $value
	 * @return  Environment  to allow method chaining
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
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
	 *
	 * @since  2.0.0
	 */
	public function set_active_application($app)
	{
		$this->active_app = $app;
		return $this;
	}

	/**
	 * Fetches the current active Application
	 *
	 * @return  Application\Base
	 *
	 * @since  2.0.0
	 */
	public function active_application()
	{
		return $this->active_app;
	}

	/**
	 * Translates a classname to the one set in the DiC classes property
	 *
	 * @param   string  $class
	 * @return  string
	 *
	 * @since  2.0.0
	 */
	public function get_class($class)
	{
		return $this->dic->get_class($class);
	}

	/**
	 * Forges a new object for the given class, supporting DI replacement
	 *
	 * @param   string|array  $classname  classname or array($obj_name, $classname)
	 * @return  object
	 *
	 * @since  2.0.0
	 */
	public function forge($classname)
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
	 *
	 * @since  2.0.0
	 */
	public function get_object($class, $name = null)
	{
		return $this->dic->get_object($class, $name);
	}
}
