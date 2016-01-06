<?php

use Terminus\Utils;

/**
 * Testing class for Terminus\Utils
 */
class UtilsTest extends PHPUnit_Framework_TestCase {

  public function testFilenameFromUrl() {
    $url  = 'https://pantheon-backups.s3.amazonaws.com/';
    $url .= 'aaa313ea-d667-4cf6-b165-31a4a03abbc0/dev/1411761319_export/';
    $url .= 'miketestsite_dev_2014-09-26T19-55-19_UTC_database.sql.gz?';
    $url .= 'Signature=dK%2FOf7EtMwbjCpmnuBJ8S8ApezE%3D&Expires=1414793205&';
    $url .= 'AWSAccessKeyId=AKIAJEYKXMCPBZQYJYXQ';
    $filename = Utils\getFilenameFromUrl($url);
    $this->assertEquals('miketestsite_dev_2014-09-26T19-55-19_UTC_database.sql.gz', $filename);
  }

  public function testSqlFromZip() {
    $target = '/tmp/miketestsite_dev_2014-10-30T18-59-07_UTC_database.sql.gz';
    $actual = Utils\sqlFromZip($target);
    $this->assertEquals('/tmp/miketestsite_dev_2014-10-30T18-59-07_UTC_database.sql', $actual);
  }

  public function testIsTest() {
    $this->assertTrue(\Terminus::isTest());
  }

  public function testDestinationIsValid() {
    $testdir = sys_get_temp_dir() . '/testdirtocreate';
    $destination = Utils\destinationIsValid($testdir);
    $this->assertFileExists($testdir);
    $this->assertEquals($testdir, $destination);
  }

  public function testIsValidEmail() {
    $this->assertFalse(Utils\isValidEmail('this_is_not_an_email_address'));
    $this->assertTrue(Utils\isValidEmail('this.is.a.valid.email@ddre.ss'));
  }

  public function testDefineConstants() {
    $this->assertTrue(Terminus);

    $this->assertTrue(defined('TERMINUS_VERSION'));
    $this->assertInternalType('string', TERMINUS_VERSION);

    $this->assertTrue(defined('TERMINUS_PROTOCOL'));
    $this->assertInternalType('string', TERMINUS_PROTOCOL);

    $this->assertTrue(defined('TERMINUS_HOST'));
    $this->assertInternalType('string', TERMINUS_HOST);

    $this->assertTrue(defined('TERMINUS_PORT'));
    $this->assertInternalType('integer', TERMINUS_PORT);

    $this->assertTrue(defined('TERMINUS_TIME_ZONE'));
    $this->assertInternalType('string', TERMINUS_TIME_ZONE);

    $this->assertTrue(defined('TERMINUS_SCRIPT'));
    $this->assertInternalType('string', TERMINUS_SCRIPT);
  }

}
