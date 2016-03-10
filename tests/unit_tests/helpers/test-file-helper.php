<?php

use Terminus\Commands\ArtCommand;
use Terminus\Helpers\FileHelper;
use Terminus\Runner;

/**
 * Testing class for Terminus\HelpersFileHelper
 */
class FileHelperTest extends PHPUnit_Framework_TestCase {

  /**
   * @var UpdateHelper
   */
  private $file_helper;

  public function __construct() {
    $command             = new ArtCommand(['runner' => new Runner()]);
    $this->file_helper = new FileHelper(compact('command'));
  }

  public function testDestinationIsValid() {
    $file_name = '/tmp/test_destination';
    setOutputDestination($file_name);
    try {
      $valid_destination = $this->file_helper->destinationIsValid($file_name);
    } catch (\Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertTrue(isset($message));

    resetOutputDestination($file_name);
    $this->file_helper->destinationIsValid('/tmp/');
  }

  public function testGetFilenameFromUrl() {
    $url  = 'https://pantheon-backups.s3.amazonaws.com/';
    $url .= 'aaa313ea-d667-4cf6-b165-31a4a03abbc0/dev/1411761319_export/';
    $url .= 'miketestsite_dev_2014-09-26T19-55-19_UTC_database.sql.gz?';
    $url .= 'Signature=dK%2FOf7EtMwbjCpmnuBJ8S8ApezE%3D&Expires=1414793205&';
    $url .= 'AWSAccessKeyId=AKIAJEYKXMCPBZQYJYXQ';
    $filename = $this->file_helper->getFilenameFromUrl($url);
    $this->assertEquals('miketestsite_dev_2014-09-26T19-55-19_UTC_database.sql.gz', $filename);
  }

  public function testLoadAsset() {
    $file = $this->file_helper->loadAsset('unicorn.txt');
    $this->assertTrue(strpos($file, 'ICAgICAg') === 0);

    try {
      $invalid_file = $this->file_helper->loadAsset('invalid');
    } catch (\Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertTrue(isset($message));
  }

  public function testSqlFromZip() {
    $target = '/tmp/miketestsite_dev_2014-10-30T18-59-07_UTC_database.sql.gz';
    $actual = $this->file_helper->sqlFromZip($target);
    $this->assertEquals('/tmp/miketestsite_dev_2014-10-30T18-59-07_UTC_database.sql', $actual);
  }

}