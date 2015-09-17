<?php
/**
 * Testing class for \Terminus\Utils
 *
 */
 class UtilsTest extends PHPUnit_Framework_TestCase {

   function testResultIsMultiObj() {
     $arraysimple = array(
       'name' => 'test',
       'key'  => 'test',
     );

     $arraymultiobj = array(
       0 => (object) array(
         'name' => 'test',
         'key'  => 'test',
       ),
     );

     $this->assertFalse( \Terminus\Utils\result_is_multiobj( $arraysimple ));
     $this->assertTrue( \Terminus\Utils\result_is_multiobj( $arraymultiobj ));
   }

  function testFilenameFromUrl() {
    $url = 'https://pantheon-backups.s3.amazonaws.com/aaa313ea-d667-4cf6-b165-31a4a03abbc0/dev/1411761319_export/miketestsite_dev_2014-09-26T19-55-19_UTC_database.sql.gz?Signature=dK%2FOf7EtMwbjCpmnuBJ8S8ApezE%3D&Expires=1414793205&AWSAccessKeyId=AKIAJEYKXMCPBZQYJYXQ';
    $filename = \Terminus\Utils\get_filename_from_url($url);
    $this->assertEquals("miketestsite_dev_2014-09-26T19-55-19_UTC_database.sql.gz",$filename);
  }

  function testSqlFromZip() {
    $target = '/tmp/miketestsite_dev_2014-10-30T18-59-07_UTC_database.sql.gz';
    $actual = \Terminus\Utils\sql_from_zip($target);
    $this->assertEquals('/tmp/miketestsite_dev_2014-10-30T18-59-07_UTC_database.sql', $actual);
  }

  function testIsTest() {
    $this->assertTrue(\Terminus::is_test());
  }

  function testDestinationIsValid() {
    $testdir = sys_get_temp_dir()."/testdirtocreate";
    $destination = \Terminus\Utils\destination_is_valid($testdir);
    $this->assertFileExists($testdir);
    $this->assertEquals($testdir,$destination);
  }
 }
