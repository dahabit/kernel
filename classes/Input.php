<?php

namespace Fuel\Kernel;

class Input
{
	/**
	 * @var  Input  parent Input object to fall back on
	 */
	protected $parent;

	/**
	 * @var  null|string  The URI that was detected automatically
	 */
	protected $detected_uri = null;

	/**
	 * @var  null|string  The URI extension that was detected automatically
	 */
	protected $detected_ext = null;

	/**
	 * @var  string  HTTP method used
	 */
	protected $http_method = 'GET';

	/**
	 * @var  array  server variables
	 */
	protected $server_vars = array();

	/**
	 * @var  array  The vars from the HTTP method (GET, POST, PUT, DELETE)
	 */
	protected $input_vars = array();

	/**
	 * @var  array  All of the variables from the URL (= GET when input method is GET)
	 */
	protected $uri_vars = array();

	/**
	 * @var  array  Cookie
	 */
	protected $cookie = array();

	/**
	 * @var  array
	 */
	protected $files = array();

	public function __construct(array $env_vars = array(), $parent = null)
	{
		isset($env_vars['server'])
			? $this->server_vars = $env_vars['server']
			: $this->server_vars =& $_SERVER;

		isset($env_vars['method'])
			? $this->http_method = $env_vars['method']
			: $this->http_method = $this->server('HTTP_X_HTTP_METHOD_OVERRIDE', $this->server('REQUEST_METHOD', 'GET'));

		if (isset($env_vars['input']))
		{
			$this->input_vars = $env_vars['input'];
		}
		else
		{
			switch ($this->http_method)
			{
				case 'DELETE':
				case 'PUT':
					parse_str(file_get_contents('php://input'), $this->input_vars);
				case 'POST':
					$this->input_vars =& $_POST;
				case 'GET':
				default:
					$this->input_vars =& $_GET;
			}
		}

		isset($env_vars['uri_vars'])
			? $this->uri_vars = $env_vars['uri_vars']
			: $this->uri_vars =& $_GET;

		isset($env_vars['cookie'])
			? $this->cookie = $env_vars['cookie']
			: $this->cookie =& $_COOKIE;

		isset($env_vars['files'])
			? $this->files = $env_vars['files']
			: $this->files =& $_FILES;

		$this->parent = $parent instanceof static ? $parent : null;
	}

	/**
	 * Detects and returns the current URI based on a number of different server
	 * variables.
	 *
	 * @return  string
	 */
	public function uri()
	{
		if ($this->detected_uri !== null)
		{
			return $this->detected_uri;
		}

		if (_env('is_cli'))
		{
			if ($uri = \Cli::option('uri') !== null)
			{
				$this->detected_uri = $uri;
			}
			else
			{
				$this->detected_uri = \Cli::option(1);
			}

			return $this->detected_uri;
		}

		// We want to use PATH_INFO if we can.
		if ( ! empty($_SERVER['PATH_INFO']))
		{
			$uri = $_SERVER['PATH_INFO'];
		}
		// Only use ORIG_PATH_INFO if it contains the path
		elseif ( ! empty($_SERVER['ORIG_PATH_INFO'])
			and ($path = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['ORIG_PATH_INFO'])) != '')
		{
			$uri = $path;
		}
		else
		{
			// Fall back to parsing the REQUEST URI
			if (isset($_SERVER['REQUEST_URI']))
			{
				$uri = $_SERVER['REQUEST_URI'];
			}
			else
			{
				throw new \RuntimeException('Unable to detect the URI.');
			}

			// Remove the base URL from the URI
			$base_url = parse_url(_env('base_url'), PHP_URL_PATH);
			if ($uri != '' and strncmp($uri, $base_url, strlen($base_url)) === 0)
			{
				$uri = substr($uri, strlen($base_url));
			}

			// If we are using an index file (not mod_rewrite) then remove it
			$index_file = _env('index_file');
			if ($index_file and strncmp($uri, $index_file, strlen($index_file)) === 0)
			{
				$uri = substr($uri, strlen($index_file));
			}

			// When index.php? is used and the config is set wrong, lets just
			// be nice and help them out.
			if ($index_file and strncmp($uri, '?/', 2) === 0)
			{
				$uri = substr($uri, 1);
			}

			// Lets split the URI up in case it contains a ?.  This would
			// indicate the server requires 'index.php?' and that mod_rewrite
			// is not being used.
			preg_match('#(.*?)\?(.*)#i', $uri, $matches);

			// If there are matches then lets set set everything correctly
			if ( ! empty($matches))
			{
				$uri = $matches[1];
				$_SERVER['QUERY_STRING'] = $matches[2];
				parse_str($matches[2], $_GET);
			}
		}

		// Strip the defined url suffix from the uri if needed
		$uri_info = pathinfo($uri);
		if ( ! empty($uri_info['extension']))
		{
			$this->detected_ext = $uri_info['extension'];
			$uri = $uri_info['dirname'].'/'.$uri_info['filename'];
		}

		return ($this->detected_uri = $uri);
	}

	/**
	 * Detects and returns the current URI extension
	 *
	 * @return  string
	 */
	public function extension()
	{
		is_null($this->detected_ext) and static::uri();

		return $this->detected_ext;
	}

	/**
	 * Get the public ip address of the user.
	 *
	 * @param   string  $default
	 * @return  string
	 */
	public function ip($default = '0.0.0.0')
	{
		if ($this->server('REMOTE_ADDR') !== null)
		{
			return $this->server('REMOTE_ADDR');
		}
		else
		{
			// detection failed, return the default
			return $default;
		}
	}

	/**
	 * Get the real ip address of the user.  Even if they are using a proxy.
	 *
	 * @return  string  the real ip address of the user
	 */
	public function real_ip($default = '0.0.0.0')
	{
		if ($this->server('HTTP_X_CLUSTER_CLIENT_IP') !== null)
		{
			return $this->server('HTTP_X_CLUSTER_CLIENT_IP');
		}

		if ($this->server('HTTP_X_FORWARDED_FOR') !== null)
		{
			return $this->server('HTTP_X_FORWARDED_FOR');
		}

		if ($this->server('HTTP_CLIENT_IP') !== null)
		{
			return $this->server('HTTP_CLIENT_IP');
		}

		if ($this->server('REMOTE_ADDR') !== null)
		{
			return $this->server('REMOTE_ADDR');
		}

		// detection failed, return the default
		return $default;
	}

	/**
	 * Return's the protocol that the request was made with
	 *
	 * @return  string
	 */
	public function protocol()
	{
		if (($this->server('HTTPS') !== null and $this->server('HTTPS') != 'off')
			or ($this->server('HTTPS') === null and $this->server('SERVER_PORT') == 443))
		{
			return 'https';
		}

		return 'http';
	}

	/**
	 * Return's whether this is an AJAX request or not
	 *
	 * @return  bool
	 */
	public function is_ajax()
	{
		return ($this->server('HTTP_X_REQUESTED_WITH') !== null)
			and strtolower($this->server('HTTP_X_REQUESTED_WITH')) === 'xmlhttprequest';
	}

	/**
	 * Return's the referrer
	 *
	 * @param   string  $default
	 * @return  string
	 */
	public function referrer($default = '')
	{
		return $this->server('HTTP_REFERER', $default);
	}

	/**
	 * Return's the input method used (GET, POST, DELETE, etc.)
	 *
	 * @return  string
	 */
	public function method()
	{
		return $this->http_method;
	}

	/**
	 * Return's the user agent
	 *
	 * @param   string  $default
	 * @return  string
	 */
	public function user_agent($default = '')
	{
		return $this->server('HTTP_USER_AGENT', $default);
	}

	/**
	 * Fetch an item from the FILE array
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public function file($index = null, $default = null)
	{
		if (is_null($index) and func_num_args() === 0)
		{
			return $this->files;
		}
		elseif ( ! array_get_dot_key($index, $this->files, $return))
		{
			return $this->parent ? $this->parent->file($index, $default) : $default;
		}

		return $return;
	}

	/**
	 * Fetch an item from the URI query string
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public function query_string($index = null, $default = null)
	{
		if (is_null($index) and func_num_args() === 0)
		{
			return $this->uri_vars;
		}
		elseif ( ! array_get_dot_key($index, $this->uri_vars, $return))
		{
			return $this->parent ? $this->parent->query_string($index, $default) : $default;
		}

		return $return;
	}

	/**
	 * Fetch an item from the input
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public function param($index = null, $default = null)
	{
		if (is_null($index) and func_num_args() === 0)
		{
			return $this->input_vars;
		}
		elseif ( ! array_get_dot_key($index, $this->input_vars, $return))
		{
			return $this->parent ? $this->parent->param($index, $default) : $default;
		}

		return $return;
	}

	/**
	 * Fetch an item from the COOKIE array
	 *
	 * @param    string  $index    The index key
	 * @param    mixed   $default  The default value
	 * @return   string|array
	 */
	public function cookie($index = null, $default = null)
	{
		if (is_null($index) and func_num_args() === 0)
		{
			return $this->cookie;
		}
		elseif ( ! array_get_dot_key($index, $this->cookie, $return))
		{
			return $this->parent ? $this->parent->cookie($index, $default) : $default;
		}

		return $return;
	}

	/**
	 * Fetch an item from the SERVER array
	 *
	 * @param   string  $index    The index key
	 * @param   mixed   $default  The default value
	 * @return  string|array
	 */
	public function server($index = null, $default = null)
	{
		if (is_null($index) and func_num_args() === 0)
		{
			return $this->server_vars;
		}
		elseif (array_get_dot_key($index, $this->server_vars, $return))
		{
			return $return;
		}
		elseif ( ! array_get_dot_key(strtoupper($index), $this->server_vars, $return))
		{
			return $this->parent ? $this->parent->server($index, $default) : $default;
		}

		return $return;
	}
}
