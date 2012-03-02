<?php
/**
 * Part of the FuelPHP framework.
 *
 * @package    Fuel\Kernel
 * @version    2.0.0
 * @license    MIT License
 * @copyright  2010 - 2012 Fuel Development Team
 */

namespace Fuel\Kernel\Application;
use Fuel\Kernel\DiC;
use Fuel\Kernel\Environment;
use Fuel\Kernel\Loader;
use Fuel\Kernel\Request;
use Fuel\Kernel\Route;

/**
 * Application Base class
 *
 * Wraps an application package into an object to work with, must be extended per application.
 *
 * @package  Fuel\Kernel
 *
 * @since  2.0.0
 */
abstract class Base
{
	/**
	 * @var  \Fuel\Kernel\Environment
	 *
	 * @since  2.0.0
	 */
	public $env;

	/**
	 * @var  \Fuel\Kernel\Loader\Loadable  the Application's own loader instance
	 *
	 * @since  2.0.0
	 */
	public $loader;

	/**
	 * @var  \Fuel\Kernel\Security\Base  the Application's security instance
	 *
	 * @since  2.0.0
	 */
	public $security;

	/**
	 * @var  \Fuel\Kernel\Notifier\Notifiable
	 *
	 * @since  2.0.0
	 */
	public $notifier;

	/**
	 * @var  \Fuel\Kernel\Data\Config
	 *
	 * @since  2.0.0
	 */
	public $config;

	/**
	 * @var  \Fuel\Kernel\Data\Language
	 *
	 * @since  2.0.0
	 */
	public $language;

	/**
	 * @var  \Fuel\Kernel\Error
	 *
	 * @since  2.0.0
	 */
	public $error;

	/**
	 * @var  array  route objects
	 *
	 * @since  2.0.0
	 */
	protected $routes = array();

	/**
	 * @var  array  packages to load
	 *
	 * @since  2.0.0
	 */
	protected $packages = array();

	/**
	 * @var  \Fuel\Kernel\Request\Base  contains the app main request object once created
	 *
	 * @since  2.0.0
	 */
	public $request;

	/**
	 * @var  \Fuel\Kernel\Request\Base  current active Request, not necessarily the main request
	 *
	 * @since  2.0.0
	 */
	protected $active_request;

	/**
	 * @var  array  active Application stack before activation of this one
	 *
	 * @since  2.0.0
	 */
	protected $_before_activate = array();

	/**
	 * @var  \Fuel\Kernel\DiC\Dependable
	 *
	 * @since  2.0.0
	 */
	public $dic;

	/**
	 * Constructor
	 *
	 * @param  \Fuel\Kernel\Environment      $env
	 * @param  Closure  $config
	 * @param  \Fuel\Kernel\Loader\Loadable  $loader
	 *
	 * @since  2.0.0
	 */
	public function __construct(Environment $env, \Closure $config, Loader\Loadable $loader)
	{
		$this->env     = $env;
		$this->loader  = $loader;

		foreach ($this->packages as $pkg)
		{
			try
			{
				$this->env->loader->load_package($pkg, Loader::TYPE_PACKAGE);
			}
			// ignore exception thrown for double package load
			catch (\RuntimeException $e) {}
		}

		call_user_func($config);

		// When not set by the closure default to Kernel DiC
		( ! $this->dic instanceof DiC\Dependable) and $this->dic = new DiC\Base($this, $this->env->dic);
		$this->setup();

		// Load the Exception Handler
		$this->error = $this->forge('Error');

		// Load main Application config
		$this->config = $this->forge('Config', (array) $this->config())->load('config.php');

		// Add Application notifier
		$this->notifier = $this->forge('Notifier', $this->config->get('observers', array()));

		// Load the Security class
		$this->security = $this->forge('Security');

		// Add main Application language
		$this->language = $this->forge('Language');

		// Add the routes
		$this->router();

		$this->notifier->notify('app_created', $this, __METHOD__);
	}

	/**
	 * Setup: this method is run before anything else right after the DiC is initialized
	 *
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function setup() {}

	/**
	 * Define the default config for this application
	 *
	 * @return  array
	 *
	 * @since  2.0.0
	 */
	public function config() {}

	/**
	 * Define the routes for this application
	 *
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	abstract public function router();

	/**
	 * Add a route to the Application
	 *
	 * @param   string        $name
	 * @param   string|array  $route
	 * @return  \Fuel\Kernel\Route\Base
	 *
	 * @since  2.0.0
	 */
	public function add_route($name, $route)
	{
		if ($route instanceof Route\Base)
		{
			$this->routes[$name] = $route;
		}
		elseif (is_array($route))
		{
			array_unshift('Route', $route);
			$this->routes[$name] = call_user_func_array(array($this, 'forge'), $route);
		}
		else
		{
			$this->routes[$name] = $this->forge('Route', $name, $route);
		}
		return $this->routes[$name];
	}

	/**
	 * Add multiple routes
	 *
	 * @param   array  $routes
	 * @return  Base
	 *
	 * @since  2.0.0
	 */
	public function add_routes(array $routes)
	{
		foreach ($routes as $name => $route)
		{
			$this->add_route($name, $route);
		}
		return $this;
	}

	/**
	 * Allow for reverse routing
	 *
	 * @param   string  $name
	 * @return  \Fuel\Kernel\Route\Base
	 * @throws  \RuntimeException
	 *
	 * @since  2.0.0
	 */
	public function get_route($name)
	{
		if ( ! isset($this->routes[$name]))
		{
			throw new \RuntimeException('Requesting an unregistered route.');
		}
		return $this->routes[$name];
	}

	/**
	 * Attempts to route a given URI to a controller (class, Closure or callback)
	 *
	 * @param   string  $uri
	 * @return  array
	 * @throws  \Fuel\Kernel\Request\Exception_404
	 *
	 * @since  2.0.0
	 */
	public function process_route($uri)
	{
		// Attempt other routes
		foreach ($this->routes as $route)
		{
			if ($route->matches($uri))
			{
				return $route->match();
			}
		}

		// If not found create a Fuel route
		$route = $this->forge('Route', $uri);
		if ($route->matches($uri))
		{
			return $route->match();
		}

		throw new Request\Exception_404($uri);
	}

	/**
	 * Create the application main request
	 *
	 * @param   string  $uri
	 * @return  \Fuel\Kernel\Request\Base
	 *
	 * @since  2.0.0
	 */
	public function request($uri)
	{
		$this->request = $this->forge('Request', $this->security->clean_uri($uri));
		return $this;
	}

	/**
	 * Execute the application main request
	 *
	 * @return  Base
	 *
	 * @since  2.0.0
	 */
	public function execute()
	{
		$this->activate();
		$this->request->execute();
		$this->deactivate();
		return $this;
	}

	/**
	 * Makes this Application the active one
	 *
	 * @return  Base  for method chaining
	 *
	 * @since  2.0.0
	 */
	public function activate()
	{
		array_push($this->_before_activate, $this->env->active_application());
		$this->env->set_active_application($this);
		return $this;
	}

	/**
	 * Deactivates this Application and reactivates the previous active
	 *
	 * @return  Base  for method chaining
	 *
	 * @since  2.0.0
	 */
	public function deactivate()
	{
		$this->env->set_active_application(array_pop($this->_before_activate));
		return $this;
	}

	/**
	 * Return the response object
	 *
	 * @return  \Fuel\Kernel\Response\Responsible
	 *
	 * @since  2.0.0
	 */
	public function response()
	{
		return $this->request->response();
	}

	/**
	 * Attempts to find one or more files in the packages
	 *
	 * @param   string  $location
	 * @param   string  $file
	 * @param   bool    $multiple
	 * @return  array|bool
	 *
	 * @since  2.0.0
	 */
	public function find_file($location, $file, $multiple = false)
	{
		$return = $multiple ? array() : false;

		// First search app
		$path = $this->loader->find_file($location, $file);
		if ($path)
		{
			if ( ! $multiple)
			{
				return $path;
			}
			$return[] = $path;
		}

		// If not found or searching for multiple continue with packages
		foreach ($this->packages as $pkg)
		{
			if ($path = $this->env->loader->package($pkg)->find_file($location, $file))
			{
				if ( ! $multiple)
				{
					return $path;
				}
				$return[] = $path;
			}
		}

		if ($multiple)
		{
			return $return;
		}

		return false;
	}

	/**
	 * Find multiple files using find_file() method
	 *
	 * @param   $location
	 * @param   $file
	 * @return  array|bool
	 *
	 * @since  2.0.0
	 */
	public function find_files($location, $file)
	{
		return $this->find_file($location, $file, true);
	}

	/**
	 * Locate a specific type of class
	 *
	 * @param   string  $type
	 * @param   string  $classname
	 * @return  bool|string  the controller classname or false on failure
	 *
	 * @since  2.0.0
	 */
	public function find_class($type, $classname)
	{
		// First try the Application loader
		if ($found = $this->loader->find_class($type, $classname))
		{
			return $found;
		}

		// if not found attempt loaded packages
		foreach ($this->packages as $pkg)
		{
			is_array($pkg) and $pkg = reset($pkg);
			if ($found = $this->env->loader->package($pkg)->find_class($type, $classname))
			{
				return $found;
			}
		}

		// all is lost
		return false;
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

	/**
	 * Sets the current active request
	 *
	 * @param  \Fuel\Kernel\Request\Base  $request
	 *
	 * @since  2.0.0
	 */
	public function set_active_request($request)
	{
		$this->active_request = $request;
	}

	/**
	 * Returns current active Request
	 *
	 * @return  \Fuel\Kernel\Request\Base
	 *
	 * @since  2.0.0
	 */
	public function active_request()
	{
		return $this->active_request;
	}
}
