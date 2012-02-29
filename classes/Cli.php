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
use Fuel\Kernel\Application;

/**
 * CLI
 *
 * Interact with the command line by accepting input options, parameters and output text.
 *
 * @package  Fuel\Kernel
 */
class Cli
{
	/**
	 * @var  \Fuel\Kernel\Application\Base
	 */
	protected $app;

	/**
	 * @var  array  arguments passed through the CLI
	 */
	protected $args = null;

	/**
	 * @var  array  named colors for text display
	 */
	protected $foreground_colors = array(
		'black'         => '0;30',
		'dark_gray'     => '1;30',
		'blue'          => '0;34',
		'dark_blue'     => '1;34',
		'light_blue'    => '1;34',
		'green'         => '0;32',
		'light_green'   => '1;32',
		'cyan'          => '0;36',
		'light_cyan'    => '1;36',
		'red'           => '0;31',
		'light_red'     => '1;31',
		'purple'        => '0;35',
		'light_purple'  => '1;35',
		'light_yellow'  => '0;33',
		'yellow'        => '1;33',
		'light_gray'    => '0;37',
		'white'         => '1;37',
	);

	/**
	 * @var  array  named colors for text background display
	 */
	protected $background_colors = array(
		'black'       => '40',
		'red'         => '41',
		'green'       => '42',
		'yellow'      => '43',
		'blue'        => '44',
		'magenta'     => '45',
		'cyan'        => '46',
		'light_gray'  => '47',
	);

	/**
	 * Constructor
	 *
	 * @param   array|null  $args  command line arguments in an array
	 * @return  void
	 */
	public function __construct($args = null)
	{
		if ( ! is_null($args))
		{
			$this->args = array();
			$this->parse_args($args);
		}
	}

	/**
	 * Magic Fuel method that is the setter for the current app
	 *
	 * @param   \Fuel\Kernel\Application\Base  $app
	 * @return  void
	 */
	public function _set_app(Application\Base $app)
	{
		$this->app = $app;

		if (is_null($this->args))
		{
			if ( ! _env('is_cli'))
			{
				throw new \Exception('Cli class cannot be instantiated empty outside of the command line.');
			}

			$input = $app->active_request() ? $app->active_request()->input : _env('input');
			$this->parse_args($input->server('argv'));
		}
	}

	/**
	 * Parses a given array into CLI arg array
	 *
	 * @param   array  $args
	 * @return  void
	 */
	protected function parse_args(array $args)
	{
		foreach ($args as $i => $arg)
		{
			$arg = explode('=', $arg);
			$this->args[$i] = reset($arg);

			if (count($arg) > 1 or strncmp(reset($arg), '-', 1) === 0)
			{
				$this->args[ltrim(reset($arg), '-')] = isset($arg[1]) ? $arg[1] : true;
			}
		}
	}

	/**
	 * Returns the option with the given name. You can also give the option
	 * number.
	 *
	 * Named options must be in the following formats:
	 * php index.php user -v --v -name=John --name=John
	 *
	 * @param   string|int  $name     the name of the option (int if unnamed)
	 * @param   mixed       $default
	 * @return  string
	 */
	public function option($name, $default = null)
	{
		if ( ! isset($this->args[$name]))
		{
			return $default;
		}
		return $this->args[$name];
	}

	/**
	 * Get input from the shell, using readline or the standard STDIN
	 *
	 * Named options must be in the following formats:
	 * php index.php user -v --v -name=John --name=John
	 *
	 * @param   string|int  $prefix  the name of the option (int if unnamed)
	 * @return  string
	 */
	public function input($prefix = '')
	{
		if (_env('readline_support'))
		{
			return readline($prefix);
		}

		echo $prefix;
		return fgets(STDIN);
	}


	/**
	 * Asks the user for input.  This can have either 1 or 2 arguments.
	 *
	 * Usage:
	 *
	 * // Waits for any key press
	 * CLI::prompt();
	 *
	 * // Takes any input
	 * $color = CLI::prompt('What is your favorite color?');
	 *
	 * // Takes any input, but offers default
	 * $color = CLI::prompt('What is your favourite color?', 'white');
	 *
	 * // Will only accept the options in the array
	 * $ready = CLI::prompt('Are you ready?', array('y','n'));
	 *
	 * @return  string  the user input
	 */
	public function prompt()
	{
		$args = func_get_args();

		$options = array();
		$output = '';
		$default = null;

		// How many we got
		$arg_count = count($args);

		// Is the last argument a boolean? True means required
		$required = end($args) === true;

		// Reduce the argument count if required was passed, we don't care about that anymore
		$required === true and --$arg_count;

		// This method can take a few crazy combinations of arguments, so lets work it out
		switch ($arg_count)
		{
			case 2:

				// E.g: $ready = CLI::prompt('Are you ready?', array('y','n'));
				if (is_array($args[1]))
				{
					list($output, $options)=$args;
				}

				// E.g: $color = CLI::prompt('What is your favourite color?', 'white');
				elseif (is_string($args[1]))
				{
					list($output, $default)=$args;
				}

			break;

			case 1:

				// No question (probably been asked already) so just show options
				// E.g: $ready = CLI::prompt(array('y','n'));
				if (is_array($args[0]))
				{
					$options = $args[0];
				}

				// Question without options
				// E.g: $ready = CLI::prompt('What did you do today?');
				elseif (is_string($args[0]))
				{
					$output = $args[0];
				}

			break;
		}

		// If a question has been asked with the read
		if ($output !== '')
		{
			$extra_output = '';

			if ($default !== null)
			{
				$extra_output = ' [ Default: "'.$default.'" ]';
			}

			elseif ($options !== array())
			{
				$extra_output = ' [ '.implode(', ', $options).' ]';
			}

			fwrite(STDOUT, $output.$extra_output.': ');
		}

		// Read the input from keyboard.
		$input = trim($this->input()) ?: $default;

		// No input provided and we require one (default will stop this being called)
		if (empty($input) and $required === true)
		{
			$this->write('This is required.');
			$this->new_line();

			$input = call_user_func_array(array($this, 'prompt'), $args);
		}

		// If options are provided and the choice is not in the array, tell them to try again
		if ( ! empty($options) and ! in_array($input, $options))
		{
			$this->write('This is not a valid option. Please try again.');
			$this->new_line();

			$input = call_user_func_array(array($this, 'prompt'), $args);
		}

		return $input;
	}

	/**
	 * Outputs a string to the cli. If you send an array it will implode them
	 * with a line break.
	 *
	 * @param   string|array  $text        the text to output, or array of lines
	 * @param   null|string   $foreground  textcolor
	 * @param   null|string   $background  text backgroundcolor
	 * @return  void
	 */
	public function write($text = '', $foreground = null, $background = null)
	{
		if (is_array($text))
		{
			$text = implode(PHP_EOL, $text);
		}

		if ($foreground or $background)
		{
			$text = $this->color($text, $foreground, $background);
		}

		fwrite(STDOUT, $text.PHP_EOL);
	}

	/**
	 * Outputs an error to the CLI using STDERR instead of STDOUT
	 *
	 * @param   string|array  $text  the text to output, or array of errors
	 * @param   null|string   $foreground  textcolor
	 * @param   null|string   $background  text backgroundcolor
	 * @return  void
	 */
	public function error($text = '', $foreground = 'light_red', $background = null)
	{
		if (is_array($text))
		{
			$text = implode(PHP_EOL, $text);
		}

		if ($foreground or $background)
		{
			$text = $this->color($text, $foreground, $background);
		}

		fwrite(STDERR, $text.PHP_EOL);
	}

	/**
	 * Beeps a certain number of times.
	 *
	 * @param  int  $num  the number of times to beep
	 */
	public function beep($num = 1)
	{
		echo str_repeat("\x07", $num);
	}

	/**
	 * Waits a certain number of seconds, optionally showing a wait message and
	 * waiting for a key press.
	 *
	 * @param   int   $seconds    number of seconds
	 * @param   bool  $countdown  show a countdown or not
	 * @return  void
	 */
	public function wait($seconds = 0, $countdown = false)
	{
		if ($countdown === true)
		{
			$time = $seconds;

			while ($time > 0)
			{
				fwrite(STDOUT, $time.'... ');
				sleep(1);
				$time--;
			}
			$this->write();
		}

		else
		{
			if ($seconds > 0)
			{
				sleep($seconds);
			}
			else
			{
				$this->write('Please wait a moment...');
				$this->input();
			}
		}
	}

	/**
	 * If operating system === windows
	 *
	 * @return  bool
	 */
 	public function is_windows()
 	{
 		return 'win' === strtolower(substr(php_uname("s"), 0, 3));
 	}

	/**
	 * Enter a number of empty lines
	 *
	 * @param   int  Number of lines to output
	 * @return  void
	 */
	public function new_line($num = 1)
	{
		// Do it once or more, write with empty string gives us a new line
		for($i = 0; $i < $num; $i++)
		{
			$this->write();
		}
	}

	/**
	 * Clears the screen of output
	 *
	 * @return  void
	 */
	public function clear_screen()
	{
		// Windows is a bit crap at this, but their terminal is tiny so shove this in
		if ($this->is_windows())
		{
			$this->new_line(40);
		}
		// Anything with a flair of Unix will handle these magic characters
		else
		{
			fwrite(STDOUT, chr(27)."[H".chr(27)."[2J");
		}
	}

	/**
	 * Returns the given text with the correct color codes for a foreground and
	 * optionally a background color.
	 *
	 * @param   string  $text        the text to color
	 * @param   string  $foreground  the foreground color
	 * @param   string  $background  the background color
	 * @return  string  the color coded string
	 */
	public function color($text, $foreground, $background = null)
	{
		$input = $this->app->active_request() ? $this->app->active_request()->input : _env('input');

		if ($this->is_windows() and ! $input->server('ANSICON'))
		{
			return $text;
		}

		if ( ! array_key_exists($foreground, $this->foreground_colors))
		{
			throw new \InvalidArgumentException('Invalid CLI foreground color: '.$foreground);
		}

		if ( $background !== null and ! array_key_exists($background, $this->background_colors))
		{
			throw new \InvalidArgumentException('Invalid CLI background color: '.$background);
		}

		$string = "\033[".$this->foreground_colors[$foreground]."m";

		if ($background !== null)
		{
			$string .= "\033[".$this->background_colors[$background]."m";
		}

		$string .= $text."\033[0m";

		return $string;
	}

	/**
	 * Spawn Background Process
	 * Launches a background process (note, provides no security itself, $call must be sanitised prior to use)
	 * @author raccettura
	 * @link http://robert.accettura.com/blog/2006/09/14/asynchronous-processing-with-php/
	 *
	 * @param   string  $call    the system call to make
	 * @param   string  $output
	 * @return  void
	 */
	public function spawn($call, $output = '/dev/null')
	{
		// Windows
		if($this->is_windows())
		{
			pclose(popen('start /b '.$call, 'r'));
		}
		// Some sort of UNIX
		else
		{
			pclose(popen($call.' > '.$output.' &', 'r'));
		}
	}
}
