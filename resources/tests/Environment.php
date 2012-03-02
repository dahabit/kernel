<?php

namespace Fuel\Kernel;

/**
 * @backupGlobals  disabled
 */
class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @var  Environment
	 */
	public $env;

	/**
	 * @var  array  to save some environment vars
	 */
	public $_saved = array();

	public function setUp()
	{
		$this->env = new Environment();
		$this->env->input = new Input();

		$this->_saved['https']   = isset($_SERVER['HTTPS']) ? $_SERVER['HTTPS'] : null;
		$_SERVER['HTTPS']        = 'on';
		$this->_saved['host']    = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
		$_SERVER['HTTP_HOST']    = 'example.com';
		$this->_saved['script']  = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : null;
		$_SERVER['SCRIPT_NAME']  = '/test/index.php';

		$this->_saved['timezone'] = date_default_timezone_get();
		date_default_timezone_set('Europe/Amsterdam');
	}

	public function tearDown()
	{
		$_SERVER['HTTPS']        = $this->_saved['https'];
		$_SERVER['HTTP_HOST']    = $this->_saved['host'];
		$_SERVER['SCRIPT_NAME']  = $this->_saved['script'];

		date_default_timezone_set($this->_saved['timezone']);
	}

	/**
	 * Test whether the static call to instance in the setUp method did return an instance of Environment
	 */
	public function test_instance()
	{
		$this->assertInstanceOf('Fuel\\Kernel\\Environment', Environment::instance());
	}

	/**
	 * Test the init method
	 */
	public function test_init()
	{
		// Mock the environment and stub methods called by init()
		$env = $this->getMock('Fuel\\Kernel\\Environment', array(
			'set_locale',
			'set_timezone',
			'php_env',
			'set_loader'
		));

		// The configuration that is given for testing
		$config = array(
			'path'          => Environment::instance()->path('fuel'),
			'language'      => 'nl',
			'timezone'      => 'America/New_York',
			'locale'        => 'nl_NL',
			'environments'  => array(),
			'loader'        => 'this_isn\'t_actually_a_loader',
		);

		// Create the method subs
		$env->expects($this->once())
			->method('set_locale')
			->with($this->equalTo($config['locale']));
		$env->expects($this->once())
			->method('set_timezone')
			->with($this->equalTo($config['timezone']));
		$env->expects($this->once())
			->method('set_loader')
			->with($this->equalTo($config['loader']));
		$env->expects($this->once())
			->method('php_env');

		// Execute the method that's being tested
		$env->init($config);

		// Some additional testing if properties were set as they are meant to
		$this->assertAttributeEquals($config['language'], 'language', $env);
		$this->assertEquals($config['path'], Environment::instance()->path('fuel'));
		$this->assertAttributeInstanceOf('Fuel\\Kernel\\DiC\\Dependable', 'dic', $env);
		$this->assertAttributeInstanceOf('Fuel\\Kernel\\Input', 'input', $env);

		return $env;
	}

	/**
	 * Test if init() fails when it's being run a second time
	 *
	 * @depends test_init
	 * @expectedException RuntimeException
	 */
	public function test_init_error_2nd_time(Environment $env)
	{
		$env->init(array());
	}

	public function test_detect_base_url()
	{
		$this->assertEquals('https://example.com/test/', $this->env->detect_base_url());
	}

	public function test_set_locale()
	{
		$this->markTestIncomplete('Have to figure out how to reliably test this.');
	}

	/**
	 * @dataProvider  timezone_provider
	 */
	public function test_set_timezone($tz)
	{
		$this->env->set_timezone($tz);
		$this->assertEquals($tz, date_default_timezone_get());
	}

	public function timezone_provider()
	{
		return array(
			array('Indian/Maldives'),
			array('America/New_York')
		);
	}
}
