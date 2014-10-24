<?php
/**
 * Testing class for \Terminus\Utils
 *
 */
 use \Terminus\Fixtures;

// this test is a demo on why globals suck
class FixturesTest extends PHPUnit_Framework_TestCase {
 private $original_argv;

 function __construct() {
   $this->original_argv = $GLOBALS['argv'];
 }

 function testArgsKey() {

    // we're going to make up some phony argv requests and make sure they get parsed right
    $keystotest = array(
      array( __FILE__, 'sites', 'show', '--site=behat-test','--nocache' ),
    );
    $expectedresults = array(
      array( 'sites:show:--site=behat:--nocache' )
    );
    for( $i=0; $i++; $i<count($keystotest) ) {
      $GLOBALS['argv'] = $keystotest[$i];
      $key = Fixtures::getArgsKey();
      $this->assertIsString( $key );
      $this->assertEquals( $expectedresults[$i], $key );
    }
 }

 function testPutAndGet() {

   // this takes the place of the global argv
   $test = array( __FILE__, 'sites', 'show', '--site=behat-test','--nocache' );

   $data = new stdClass;
   $data->file = __FILE__;
   $data->msg = "success";
   $GLOBALS['argv'] = $test;
   Fixtures::put( "test_fixture", json_encode($data) );

   // test manually
   $this->assertFileExists(CLI_ROOT.'/fixtures/sites:show:--site=behat-test:--nocache/test_fixture');
   $content = json_decode(file_get_contents(CLI_ROOT.'/fixtures/sites:show:--site=behat-test:--nocache/test_fixture'));
   $this->assertInstanceOf( 'stdClass', $content );
   $this->assertEquals( "success", $content->msg );

   // now test the get method
   $content = Fixtures::get("test_fixture");
   $content = json_decode($content);
   $this->assertInstanceOf( 'stdClass', $content );
   $this->assertEquals( "success", $content->msg );

 }

 public function __desctruct() {
   $GLOBALS['argv'] = $this->original_argv;
 }

}
