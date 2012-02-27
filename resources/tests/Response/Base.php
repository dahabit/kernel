<?php

namespace Fuel\Kernel\Response;

/**
 * @backupGlobals  disabled
 */
class BaseTest extends \PHPUnit_Framework_TestCase
{
	public function test_construct()
	{
		$body = 'Test';
		$status = 201;
		$header = array('Joke' => 'is this');
		$header_translated = array('Joke' => array('is this'));
		$response = new Base($body, $status, $header);

		$this->assertAttributeEquals($body, 'body', $response);
		$this->assertAttributeEquals($status, 'status', $response);
		$this->assertAttributeEquals($header_translated, 'headers', $response);
	}

	public function test_set_status()
	{
		$status = 400;
		$response = new Base('', $status);
		$response->set_status($status);
		$this->assertAttributeEquals($status, 'status', $response);
	}

	public function test_set_header()
	{
		$header = array('Joke' => 'is this');
		$header_translated = array('Joke' => array('is this'));
		$header2 = array('2nd' => 'test');
		$header2_translated = array('Joke' => array('is this'), '2nd' => array('test'));
		$header3 = array('2nd' => '3rd');
		$header3_translated = array('Joke' => array('is this'), '2nd' => array('test', '3rd'));
		$response = new Base();

		// Just add a header
		$response->set_header(key($header), reset($header));
		$this->assertAttributeEquals($header_translated, 'headers', $response);

		// Add a second header
		$response->set_header(key($header2), reset($header2));
		$this->assertAttributeEquals($header2_translated, 'headers', $response);

		// Add a 3rd header to the same key as the second
		$response->set_header(key($header3), reset($header3), false);
		$this->assertAttributeEquals($header3_translated, 'headers', $response);

		// Overwrite the last 2 calls
		$response->set_header(key($header2), reset($header2), true);
		$this->assertAttributeEquals($header2_translated, 'headers', $response);
	}

	public function test_get_one_header()
	{
		$response = new Base();
		$key = 'test';
		$val1 = '1st';
		$val2 = '2nd';

		// Add a single header
		$response->set_header($key, $val1);
		$this->assertEquals($val1, $response->get_header($key));

		// Add a second header, which overwrites in the context of get_header() when returning one result
		$response->set_header($key, $val2, false);
		$this->assertEquals($val2, $response->get_header($key));
	}

	public function test_get_all_headers()
	{
		$response = new Base();
		$key = 'test';
		$val1 = '1st';
		$val2 = '2nd';

		// Add a single header
		$response->set_header($key, $val1);
		$this->assertEquals(array($val1), $response->get_header($key, null, true));

		// Add a second header
		$response->set_header($key, $val2, false);
		$this->assertEquals(array($val1, $val2), $response->get_header($key, null, true));
	}

	public function test_body_get()
	{
		$body = 'Testbody';
		$response = new Base($body);
		$this->assertEquals($body, $response->body());
	}

	public function test_body_set()
	{
		$body = 'Testbody';
		$response = new Base();
		$response->body($body);
		$this->assertAttributeEquals($body, 'body', $response);
	}

	public function test_send_headers()
	{
		$this->markTestIncomplete('Will need think about how to implement this.');
	}

	public function test_magic_tostring()
	{
		$body = 'Testbody';
		$response = new Base($body);
		$this->assertEquals($body, strval($response));
	}
}
