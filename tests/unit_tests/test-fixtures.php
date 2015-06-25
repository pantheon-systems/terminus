<?php
/**
 * Testing class for \Terminus\Utils
 *
 */
 use \Terminus\Fixtures;

// this test is a demo on why globals suck
class FixturesTest extends PHPUnit_Framework_TestCase {

 function testPutAndGet() {

   // this takes the place of the global argv
   $test = array( 'sites/test/one', 'POST' );

   $data = new stdClass;
   $data->file = __FILE__;
   $data->msg = "success";
   Fixtures::put($test, $data);

   // test manually
   $filename = TEST_DIR . '/fixtures/POSTsites-test-one';
   $this->assertFileExists($filename);

   $content = unserialize(file_get_contents($filename));
   $this->assertInstanceOf( get_class($content), $content );
   $this->assertEquals( "success", $content->msg );

   // now test the get method
   $content = Fixtures::get($test);
   $this->assertInstanceOf( 'stdClass', $content );
   $this->assertEquals( "success", $content->msg );

 }

}
