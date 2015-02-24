<?php
/**
 * Testing class for \Terminus\Utils
 *
 */
 class UtilsTest extends PHPUnit_Framework_TestCase {

   function testIsHermes() {
     if( "dashboard.getpantheon.com" === TERMINUS_HOST ) {
       $this->assertTrue(\Terminus\Utils\is_hermes());
     } else {
       $this->assertFalse(\Terminus\Utils\is_hermes());
     }
   }

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
     // real world results.
     $object1 = '{"params": {"environment_id": "testlog", "deploy": {"clone_files": {"from_environment": "testlog"}, "annotation": "Create the environment.", "clone_database": {"from_environment": "testlog"}}}, "role": "owner", "site_id": "aaa313ea-d667-4cf6-b165-31a4a03abbc0", "task_ids": ["b76f4a72-bc51-11e4-b8ed-bc764e111d20", "b88cc1c8-bc51-11e4-b8ed-bc764e111d20", "b88ee85e-bc51-11e4-b8ed-bc764e111d20", "b891de74-bc51-11e4-b8ed-bc764e111d20", "b8be13cc-bc51-11e4-b8ed-bc764e111d20", "b8befdf0-bc51-11e4-b8ed-bc764e111d20", "b8bfc19a-bc51-11e4-b8ed-bc764e111d20", "b8d836f8-bc51-11e4-b8ed-bc764e111d20", "b8da08f2-bc51-11e4-b8ed-bc764e111d20", "b8dcf65c-bc51-11e4-b8ed-bc764e111d20", "b8e2c0a0-bc51-11e4-b8ed-bc764e111d20", "b8e532fe-bc51-11e4-b8ed-bc764e111d20", "b8fcfbd2-bc51-11e4-b8ed-bc764e111d20", "b8fec3ae-bc51-11e4-b8ed-bc764e111d20", "b900cf8c-bc51-11e4-b8ed-bc764e111d20", "b91e6c7c-bc51-11e4-b8ed-bc764e111d20", "b91fbeb0-bc51-11e4-b8ed-bc764e111d20", "b92315c4-bc51-11e4-b8ed-bc764e111d20", "b9267f98-bc51-11e4-b8ed-bc764e111d20"], "type": "create_cloud_development_environment", "user_id": "94212cf6-b7e1-44fc-bfc8-8a48214aa5fd", "waiting_for_task_id": "b88cc1c8-bc51-11e4-b8ed-bc764e111d20", "id": "b76f40ae-bc51-11e4-b8ed-bc764e111d20", "key": "1424800800", "environment": null, "environment_id": null, "result": null, "keep_forever": false, "final_task_id": null, "created_at": 1424801988.385195, "total_time": null, "active_description": "Creating a cloud development environment", "description": "Create a cloud development environment", "final_task": null}';
     $this->assertFalse(\Terminus\utils\result_is_multiobj(json_decode($object1)));
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
