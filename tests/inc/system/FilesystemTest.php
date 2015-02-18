<?php

require_once dirname(__FILE__) . '/../../../inc/system/class.Filesystem.php';

/**
 * Test class for Request.
 * Generated by PHPUnit on 2012-03-11 at 12:05:26.
 */
class FilesystemTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Request
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {}

	/**
	 * @covers Filesystem::stringToBytes
	 */
	public function testStringToBytes() {
		$this->assertEquals( Filesystem::stringToBytes("8M"), 8388608 );
		$this->assertEquals( Filesystem::stringToBytes("1k"), 1024 );
		$this->assertEquals( Filesystem::stringToBytes("1024"), 1024 );
		$this->assertEquals( Filesystem::stringToBytes("0"), 0 );
	}

	/**
	 * @covers Filesystem::bytesToString
	 */
	public function testBytesToString() {
		$this->assertEquals( Filesystem::bytesToString(8388608), "8.00 MB" );
		$this->assertEquals( Filesystem::bytesToString(1024*1024), "1.00 MB" );
		$this->assertEquals( Filesystem::bytesToString(1024), "1.00 kB" );
		$this->assertEquals( Filesystem::bytesToString(1023), "1023 B" );
		$this->assertEquals( Filesystem::bytesToString(0), "0 B" );
	}

}

?>
