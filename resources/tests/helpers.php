<?php

/**
 * @backupGlobals  disabled
 */
class helpersTest extends \PHPUnit_Framework_TestCase
{
	public function array_provider()
	{
		return array(
			array(
				array(
					'1st' => 'level',
					'first' => array(
						'2nd' => 'level',
						'second' => array(
							'3rd' => 'final'
						),
					),
				),
			),
		);
	}

	/**
	 * @dataProvider  array_provider
	 */
	public function test_array_set_dot_key($array)
	{
		$value = 'changed';

		array_set_dot_key('1st', $array, $value);
		$this->assertEquals($value, $array['1st']);

		array_set_dot_key('first.2nd', $array, $value);
		$this->assertEquals($value, $array['first']['2nd']);

		array_set_dot_key('first.second.3rd', $array, $value);
		$this->assertEquals($value, $array['first']['second']['3rd']);

		array_set_dot_key('first.4th', $array, $value);
		$this->assertEquals($value, $array['first']['4th']);

		array_set_dot_key('fifth.sixth.7th', $array, $value);
		$this->assertEquals($value, $array['fifth']['sixth']['7th']);
	}

	/**
	 * @dataProvider  array_provider
	 */
	public function test_array_get_dot_key($array)
	{
		array_get_dot_key('1st', $array, $return1);
		$this->assertEquals('level', $return1);

		array_get_dot_key('first.2nd', $array, $return2);
		$this->assertEquals('level', $return2);

		array_get_dot_key('first.second.3rd', $array, $return3);
		$this->assertEquals('final', $return3);

		array_get_dot_key('first.4th', $array, $return4);
		$this->assertEquals(null, $return4);

		array_get_dot_key('fifth.sixth.7th', $array, $return5);
		$this->assertEquals(null, $return5);
	}
}
