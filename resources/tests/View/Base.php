<?php

namespace Fuel\Kernel\View;

/**
 * @backupGlobals  disabled
 */
class BaseTest extends \PHPUnit_Framework_TestCase
{
	public function test_construct()
	{
		$path = 'path/to/view';
		$data = array(
			'param' => 'value'
		);
		$view = new Base($path, $data);
		$this->assertAttributeEquals($path, '_path', $view);
		$this->assertEquals($data['param'], $view->param);
	}

	public function test_construct_set_app()
	{
		$this->markTestIncomplete('Will need fake app for testing stuff like this.');
	}

	/**
	 * @depends  test_construct_set_app
	 */
	public function test_set_filename()
	{
		$this->markTestIncomplete('Will need fake app for testing stuff like this.');
	}

	public function test_set_template()
	{
		$template = 'This is a template';
		$view = new Base('this will be nulled');
		$view->set_template($template);
		$this->assertAttributeEquals(null, '_path', $view);
		$this->assertAttributeEquals($template, '_template', $view);
	}

	public function test_magic_set_get()
	{
		$view = new Base();
		$value = 'test';
		$view->test = $value;
		$this->assertEquals($value, $view->test);
	}

	/**
	 * @depends  test_construct_set_app
	 */
	public function test_render()
	{
		$this->markTestIncomplete('Will need a Parser object from an App for this.');
	}

	/**
	 * @depends  test_render
	 */
	public function test_magic_tostring()
	{
		$this->markTestIncomplete('Will need a Parser object from an App for this.');
	}
}
