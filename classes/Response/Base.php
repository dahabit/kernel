<?php

namespace Fuel\Kernel\Response;
use Fuel\Kernel\Application;

class Base implements Responsible
{
	/**
	 * @var  array  An array of status codes and messages
	 */
	public static $statuses = array(
		100 => 'Continue',
		101 => 'Switching Protocols',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		507 => 'Insufficient Storage',
		509 => 'Bandwidth Limit Exceeded'
	);

	/**
	 * @var  int  The HTTP status code
	 */
	public $status = 200;

	/**
	 * @var  array  An array of headers
	 */
	public $headers = array();

	/**
	 * @var  string  The content of the response
	 */
	public $body = null;

	/**
	 * @var  \Fuel\Kernel\Request\Base
	 */
	public $request;

	/**
	 * Sets up the response with a body and a status code.
	 *
	 * @param  string  $body    The response body
	 * @param  string  $status  The response status
	 */
	public function __construct($body = '', $status = 200, array $headers = array())
	{
		$this->body    = $body;
		$this->status  = $status;
		foreach ($headers as $k => $v)
		{
			$this->set_header($k, $v);
		}
	}

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param   \Fuel\Kernel\Application\Base  $app
	 */
	public function _set_app(Application\Base $app)
	{
		$this->request = $app->active_request();
	}

	/**
	 * Sets the response status code
	 *
	 * @param   string  $status  The status code
	 * @return  $this
	 */
	public function set_status($status = 200)
	{
		$this->status = $status;
		return $this;
	}

	/**
	 * Adds a header to the queue
	 *
	 * @param   string  $name     The header name
	 * @param   string  $value    The header value
	 * @param   string  $replace  Whether to replace existing value for the header
	 * @return  $this
	 */
	public function set_header($name, $value, $replace = true)
	{
		if ($replace or ! isset($this->headers[$name]))
		{
			$this->headers[$name] = array($value);
		}
		else
		{
			array_push($this->headers[$name], $value);
		}

		return $this;
	}

	/**
	 * Gets header information from the queue
	 *
	 * @param   string  $name     The header name, or null for all headers
	 * @param   mixed   $default  Default return when header not set
	 * @param   bool    $all      Whether to return all or just the last
	 * @return  array|string
	 */
	public function get_header($name = null, $default = null, $all = false)
	{
		if (func_num_args() == 0)
		{
			return $this->headers;
		}
		elseif ( ! isset($this->headers[$name]))
		{
			return $default;
		}

		return $all ? $this->headers[$name] : end($this->headers[$name]);
	}

	/**
	 * Sets (or returns) the body for the response
	 *
	 * @param   string  $value  The response content
	 * @return  $this|string
	 */
	public function body($value = null)
	{
		if (func_num_args() == 0)
		{
			return $this->body;
		}

		$this->body = $value;
		return $this;
	}

	/**
	 * Sends the headers if they haven't already been sent.  Returns whether
	 * they were sent or not.
	 *
	 * @return  Base
	 * @throws  \RuntimeException
	 */
	public function send_headers()
	{
		$input = property_exists($this->request, 'input') ? $this->request->input : _env('input');

		if (headers_sent())
		{
			throw new \RuntimeException('Cannot send headers, headers already sent.');
		}

		// Send the protocol/status line first, FCGI servers need different status header
		if ( ! empty($_SERVER['FCGI_SERVER_VERSION']))
		{
			header('Status: '.$this->status.' '.static::$statuses[$this->status]);
		}
		else
		{
			$protocol = $input->server('SERVER_PROTOCOL') ? $input->server('SERVER_PROTOCOL') : 'HTTP/1.1';
			header($protocol.' '.$this->status.' '.static::$statuses[$this->status]);
		}

		foreach ($this->headers as $name => $values)
		{
			foreach ($values as $value)
			{
				// Create the header and send it
				is_string($name) and $value = "{$name}: {$value}";
				header($value, true);
			}
		}

		return $this;
	}

	/**
	 * Returns the body as a string.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return (string) $this->body();
	}
}