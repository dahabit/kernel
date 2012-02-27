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

	public function test_init()
	{
		$this->markTestIncomplete('Have to figure out how to test this.');
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
