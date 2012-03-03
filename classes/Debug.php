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
 * Debug class
 *
 * The Debug class is a simple utility for debugging variables, objects, arrays, etc by outputting
 * information to the display.
 *
 * @package  Fuel\Kernel
 *
 * @since  1.0.0
 */
class Debug
{
	/**
	 * @var  Environment
	 */
	protected $env;


	/**
	 * @var  bool  whether the JS has been output already
	 */
	protected $js_displayed = false;

	/**
	 * @var  array
	 */
	protected $files = array();

	/**
	 * Fuel method that is the setter for the app's environment
	 *
	 * @param   \Fuel\Kernel\Environment  $env
	 * @return  void
	 *
	 * @since  2.0.0
	 */
	public function _set_env(Environment $env)
	{
		$this->env = $env;
	}

	/**
	 * Output a mixed variable to the browser
	 *
	 * @return  void
	 */
	public function dump()
	{

		// If being called from within, show the file above in the backtrack
		$backtrace = debug_backtrace();
		if (strpos($backtrace[0]['file'], 'kernel/classes/Debug.php') !== false)
		{
			$callee = $backtrace[1];
		}
		else
		{
			$callee = $backtrace[0];
		}

		$arguments = func_get_args();
		$callee['file'] = $this->env->clean_path($callee['file']);

		echo '<div style="font-size: 13px;background: #EEE !important; border:1px solid #666; color: #000 !important; padding:10px;">';
		echo '<h1 style="border-bottom: 1px solid #CCC; padding: 0 0 5px 0; margin: 0 0 5px 0; font: bold 120% sans-serif;">'.$callee['file'].' @ line: '.$callee['line'].'</h1>';
		echo '<pre style="overflow:auto;font-size:100%;">';

		$count = count($arguments);
		for ($i = 1; $i <= $count; $i++)
		{
			echo '<strong>Variable #'.$i.':</strong>'.PHP_EOL;
			var_dump($arguments[$i - 1]);
			echo PHP_EOL.PHP_EOL;
		}

		echo "</pre>";
		echo "</div>";
	}

	/**
	 * Output a mixed variable to the browser
	 *
	 * @return  void
	 */
	public function inspect()
	{
		$backtrace = debug_backtrace();

		// If being called from within, show the file above in the backtrack
		if (strpos($backtrace[0]['file'], 'core/classes/debug.php') !== FALSE)
		{
			$callee = $backtrace[1];
			$label = $backtrace[1]['function'];
		}
		else
		{
			$callee = $backtrace[0];
			$label = 'Debug';
		}

		$arguments = func_get_args();
		$total_arguments = count($arguments);

		$callee['file'] = $this->env->clean_path($callee['file']);

		if ( ! $this->js_displayed)
		{
			echo <<<JS
<script type="text/javascript">function fuel_debug_toggle(a){if(document.getElementById){if(document.getElementById(a).style.display=="none"){document.getElementById(a).style.display="block"}else{document.getElementById(a).style.display="none"}}else{if(document.layers){if(document.id.display=="none"){document.id.display="block"}else{document.id.display="none"}}else{if(document.all.id.style.display=="none"){document.all.id.style.display="block"}else{document.all.id.style.display="none"}}}};</script>
JS;
			$this->js_displayed = true;
		}
		echo '<div style="font-size: 13px;background: #EEE !important; border:1px solid #666; color: #000 !important; padding:10px;">';
		echo '<h1 style="border-bottom: 1px solid #CCC; padding: 0 0 5px 0; margin: 0 0 5px 0; font: bold 120% sans-serif;">'.$callee['file'].' @ line: '.$callee['line'].'</h1>';
		echo '<pre style="overflow:auto;font-size:100%;">';
		$i = 0;
		foreach ($arguments as $argument)
		{
			echo '<strong>'.$label.' #'.(++$i).' of '.$total_arguments.'</strong>:<br />';
				echo static::format('...', $argument);
			echo '<br />';
		}

		echo "</pre>";
		echo "</div>";
	}

	/**
	 * Formats the given $var's output in a foldable interface
	 *
	 * @param   string  $name   the name of the var
	 * @param   mixed   $var    the variable
	 * @param   int     $level  the indentation level
	 * @param   string  $indent_char  the indentation character
	 * @return  string  the formatted string.
	 */
	public static function format($name, $var, $level = 0, $indent_char = '&nbsp;&nbsp;&nbsp;&nbsp;')
	{
		$return = str_repeat($indent_char, $level);
		if (is_array($var))
		{
			$id = 'fuel_debug_'.mt_rand();
			if (count($var) > 0)
			{
				$return .= "<a href=\"javascript:fuel_debug_toggle('$id');\"><strong>{$name}</strong></a>";
			}
			else
			{
				$return .= "<strong>{$name}</strong>";
			}
			$return .=  " (Array, ".count($var)." elements)\n";

			$sub_return = '';
			foreach ($var as $key => $val)
			{
				$sub_return .= static::format($key, $val, $level + 1);
			}

			if (count($var) > 0)
			{
				$return .= "<span id=\"$id\" style=\"display: none;\">$sub_return</span>";
			}
			else
			{
				$return .= $sub_return;
			}
		}
		elseif (is_string($var))
		{
			$return .= "<strong>{$name}</strong> (String, ".strlen($var)." characters): \"{$var}\"\n";
		}
		elseif (is_float($var))
		{
			$return .= "<strong>{$name}</strong> (Float): {$var}\n";
		}
		elseif (is_long($var))
		{
			$return .= "<strong>{$name}</strong> (Integer): {$var}\n";
		}
		elseif (is_null($var))
		{
			$return .= "<strong>{$name}</strong> (Null): null\n";
		}
		elseif (is_bool($var))
		{
			$return .= "<strong>{$name}</strong> (Boolean): ".($var ? 'true' : 'false')."\n";
		}
		elseif (is_double($var))
		{
			$return .= "<strong>{$name}</strong> (Double): {$var}\n";
		}
		elseif (is_object($var))
		{
			$id = 'fuel_debug_'.mt_rand();
			$vars = get_object_vars($var);
			if (count($vars) > 0)
			{
				$return .= "<a href=\"javascript:fuel_debug_toggle('$id');\"><strong>{$name}</strong></a>";
			}
			else
			{
				$return .= "<strong>{$name}</strong>";
			}
			$return .= " (Object): ".get_class($var)."\n";

			$sub_return = '';
			foreach ($vars as $key => $val)
			{
				$sub_return .= static::format($key, $val, $level + 1);
			}

			if (count($vars) > 0)
			{
				$return .= "<span id=\"$id\" style=\"display: none;\">$sub_return</span>";
			}
			else
			{
				$return .= $sub_return;
			}
		}
		else
		{
			$return .= "<strong>{$name}</strong>: {$var}\n";
		}
		return $return;
	}

	/**
	 * Returns the debug lines from the specified file
	 *
	 * @param   string  $filepath   the file path
	 * @param   int     $line_num   the line number
	 * @param   bool    $highlight  whether to use syntax highlighting or not
	 * @param   int     $padding    the amount of line padding
	 * @return  array
	 */
	public function file_lines($filepath, $line_num, $highlight = true, $padding = 5)
	{
		// We cache the entire file to reduce disk IO for multiple errors
		if ( ! isset($this->files[$filepath]))
		{
			$this->files[$filepath] = file($filepath, FILE_IGNORE_NEW_LINES);
			array_unshift($this->files[$filepath], '');
		}

		$start = $line_num - $padding;
		if ($start < 0)
		{
			$start = 0;
		}

		$length = ($line_num - $start) + $padding + 1;
		if (($start + $length) > count($this->files[$filepath]) - 1)
		{
			$length = NULL;
		}

		$debug_lines = array_slice($this->files[$filepath], $start, $length, TRUE);

		if ($highlight)
		{
			$to_replace = array('<code>', '</code>', '<span style="color: #0000BB">&lt;?php&nbsp;', "\n");
			$replace_with = array('', '', '<span style="color: #0000BB">', '');

			foreach ($debug_lines as & $line)
			{
				$line = str_replace($to_replace, $replace_with, highlight_string('<?php ' . $line, TRUE));
			}
		}

		return $debug_lines;
	}

	/**
	 * Outputs the debug_backtrace() array
	 *
	 * @return  void
	 */
	public function backtrace()
	{
		$this->dump(debug_backtrace());
	}

	/**
	 * Prints a list of all currently declared classes.
	 *
	 * @return  void
	 */
	public function classes()
	{
		$this->dump(get_declared_classes());
	}

	/**
	 * Prints a list of all currently declared interfaces (PHP5 only).
	 *
	 * @return  void
	 */
	public function interfaces()
	{
		$this->dump(get_declared_interfaces());
	}

	/**
	 * Prints a list of all currently included (or required) files.
	 *
	 * @return  void
	 */
	public function includes()
	{
		$this->dump(get_included_files());
	}

	/**
	 * Prints a list of all currently declared functions.
	 *
	 * @return  void
	 */
	public function functions()
	{
		$this->dump(get_defined_functions());
	}

	/**
	 * Prints a list of all currently declared constants.
	 *
	 * @return  void
	 */
	public function constants()
	{
		$this->dump(get_defined_constants());
	}

	/**
	 * Prints a list of all currently loaded PHP extensions.
	 *
	 * @return  void
	 */
	public function extensions()
	{
		$this->dump(get_loaded_extensions());
	}

	/**
	 * Prints a list of all HTTP request headers.
	 *
	 * @return  void
	 */
	public function headers()
	{
		$this->dump(getallheaders());
	}

	/**
	 * Prints a list of the configuration settings read from <i>php.ini</i>
	 *
	 * @return  void
	 */
	public function phpini()
	{
		if ( ! is_readable(get_cfg_var('cfg_file_path')))
		{
			return;
		}

		// render it
		$this->dump(parse_ini_file(get_cfg_var('cfg_file_path'), true));
	}

	/**
	 * Benchmark anything that is callable
	 *
	 * @param   callback  $callable
	 * @param   array     $params
	 * @return  array
	 */
	public function benchmark($callable, array $params = array())
	{
		// get the before-benchmark time
		if (function_exists('getrusage'))
		{
			$dat = getrusage();
			$utime_before = $dat['ru_utime.tv_sec'] + round($dat['ru_utime.tv_usec']/1000000, 4);
			$stime_before = $dat['ru_stime.tv_sec'] + round($dat['ru_stime.tv_usec']/1000000, 4);
		}
		else
		{
			list($usec, $sec) = explode(" ", microtime());
			$utime_before = ((float)$usec + (float)$sec);
			$stime_before = 0;
		}

		// call the function to be benchmarked
		$result = is_callable($callable) ? call_user_func_array($callable, $params) : null;

		// get the after-benchmark time
		if (function_exists('getrusage'))
		{
			$dat = getrusage();
			$utime_after = $dat['ru_utime.tv_sec'] + round($dat['ru_utime.tv_usec']/1000000, 4);
			$stime_after = $dat['ru_stime.tv_sec'] + round($dat['ru_stime.tv_usec']/1000000, 4);
		}
		else
		{
			list($usec, $sec) = explode(" ", microtime());
			$utime_after = ((float)$usec + (float)$sec);
			$stime_after = 0;
		}

		return array(
			'user' => sprintf('%1.6f', $utime_after - $utime_before),
			'system' => sprintf('%1.6f', $stime_after - $stime_before),
			'result' => $result
		);
	}
}
