<?php

use Terminus\Utils;

/**
 * Testing class for Terminus\Utils
 */
class UtilsTest extends PHPUnit_Framework_TestCase {

  /**
   * @vcr utils#checkCurrentVersion
   */
  public function testCheckCurrentVersion() {
    $current_version = Utils\checkCurrentVersion();
    preg_match("/\d+\.\d+\.\d+/", $current_version, $matches);
    $this->assertEquals(count($matches), 1);
  }

  /**
   * @vcr utils#checkCurrentVersion
   */
  public function testCheckForUpdate() {
    $log_file = getLogFileName();
    setOutputDestination($log_file);
    Terminus::getCache()->putData(
      'latest_release',
      ['check_date' => strtotime('8 days ago')]
    );
    Utils\checkForUpdate(getLogger());
    $file_contents = explode("\n", file_get_contents($log_file));
    $this->assertFalse(
      strpos(array_pop($file_contents), 'An update to Terminus is available.')
    );
    resetOutputDestination($log_file);
  }

  public function testColorize() {
    $string = "That's one small step for a man, one giant leap for mankind.";

    $colorized = Utils\colorize($string);
    $this->assertEquals($string, $colorized);
  }

  public function testDefineConstants() {
    unset($_SERVER['Terminus']);
    unset($_SERVER['TERMINUS_VERSION']);
    unset($_SERVER['TERMINUS_PROTOCOL']);
    unset($_SERVER['TERMINUS_HOST']);
    unset($_SERVER['TERMINUS_PORT']);
    unset($_SERVER['TERMINUS_TIME_ZONE']);
    unset($_SERVER['TERMINUS_SCRIPT']);
    Utils\defineConstants();
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

  public function testDestinationIsValid() {
    $file_name = '/tmp/test_destination';
    setOutputDestination($file_name);
    try {
      $valid_destination = Utils\destinationIsValid($file_name);
    } catch (\Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertTrue(isset($message));

    resetOutputDestination($file_name);
    Utils\destinationIsValid($file_name);
    resetOutputDestination($file_name);
    Utils\destinationIsValid('/tmp/');
  }

  public function testGetFilenameFromUrl() {
    $url  = 'https://pantheon-backups.s3.amazonaws.com/';
    $url .= 'aaa313ea-d667-4cf6-b165-31a4a03abbc0/dev/1411761319_export/';
    $url .= 'miketestsite_dev_2014-09-26T19-55-19_UTC_database.sql.gz?';
    $url .= 'Signature=dK%2FOf7EtMwbjCpmnuBJ8S8ApezE%3D&Expires=1414793205&';
    $url .= 'AWSAccessKeyId=AKIAJEYKXMCPBZQYJYXQ';
    $filename = Utils\getFilenameFromUrl($url);
    $this->assertEquals('miketestsite_dev_2014-09-26T19-55-19_UTC_database.sql.gz', $filename);
  }

  public function testGetVendorPaths() {
    $vendor_paths = Utils\getVendorPaths();
    $this->assertInternalType('array', $vendor_paths);
    foreach ($vendor_paths as $path) {
      $this->assertTrue(strpos($path, TERMINUS_ROOT) === 0);
      $this->assertTrue(strpos($path, '/vendor') !== false);
    }
  }

  public function testImportEnvironmentVariables() {
    $file_name = '.env';
    $this->assertFalse(getenv('TERMINUS_TEST_VAR'));
    setOutputDestination($file_name);
    file_put_contents($file_name, 'TERMINUS_TEST_VAR="ambrosia"');
    Utils\importEnvironmentVariables();
    resetOutputDestination($file_name);
    $this->assertEquals(getenv('TERMINUS_TEST_VAR'), 'ambrosia');
  }

  public function testIsValidEmail() {
    $this->assertFalse(Utils\isValidEmail('this_is_not_an_email_address'));
    $this->assertTrue(Utils\isValidEmail('this.is.a.valid.email@ddre.ss'));
  }

  public function testIsTest() {
    $this->assertTrue(Utils\isTest());

    putenv('CLI_TEST_MODE=');
    putenv("TERMINUS_TEST_IGNORE=1");
    putenv("VCR_CASSETTE=1");
    $this->assertFalse(Utils\isTest());
    putenv("TERMINUS_TEST_IGNORE=");
    putenv('CLI_TEST_MODE=1');
    putenv("VCR_CASSETTE=");
  }

  public function testIsWindows() {
    $os         = shell_exec('uname');
    $is_windows = Utils\isWindows();
    $this->assertEquals(strpos($os, 'NT') !== false, $is_windows);

    putenv("TERMINUS_TEST_IGNORE=1");
    $this->assertTrue(Utils\isWindows());
    putenv("TERMINUS_TEST_IGNORE=");
  }

  public function testLoadAsset() {
    $file = Utils\loadAsset('unicorn.txt');
    $this->assertTrue(strpos($file, 'ICAgICAg') === 0);

    try {
      $invalid_file = Utils\loadAsset('invalid');
    } catch (\Exception $e) {
      $message = $e->getMessage();
    }
    $this->assertTrue(isset($message));
  }

  public function testLoadDependencies() {
    $file_name = TERMINUS_ROOT . '/vendor/autoload.php';
    Utils\loadDependencies();
    $included_files = get_included_files();
    $is_included = array_search($file_name, $included_files) !== false;
    $this->assertTrue($is_included);
  }

  public function testLoadFile() {
    $file_name = '/tmp/testfile';
    setOutputDestination($file_name);
    Utils\loadFile($file_name);
    resetOutputDestination($file_name);
    $included_files = get_included_files();
    $is_included = (
      (array_search($file_name, $included_files) !== false)
      || (array_search("/private$file_name", $included_files) !== false)
    );
    $this->assertTrue($is_included);
  }

  public function testParseUrl() {
    $url = 'https://pantheon.io';
    $parts = Utils\parseUrl($url);
    $this->assertEquals(['scheme' => 'https', 'host' => 'pantheon.io'], $parts);

    $url = 'getpantheon.com';
    $parts = Utils\parseUrl($url);
    $this->assertEquals(['scheme' => 'http', 'host' => 'getpantheon.com'], $parts);
  }

  public function testSanitizeName() {
    $name           = '~My Test Site~';
    $sanitized_name = Utils\sanitizeName($name);
    $this->assertEquals('my-test-site', $sanitized_name);

    $name           = "Pantheon's The Best!";
    $sanitized_name = Utils\sanitizeName($name);
    $this->assertEquals('pantheons-the-best', $sanitized_name);
  }

  public function testSqlFromZip() {
    $target = '/tmp/miketestsite_dev_2014-10-30T18-59-07_UTC_database.sql.gz';
    $actual = Utils\sqlFromZip($target);
    $this->assertEquals('/tmp/miketestsite_dev_2014-10-30T18-59-07_UTC_database.sql', $actual);
  }

  public function testStripSensitiveData() {
    $data          = [
      'password' => 'password',
      'key' => 'value',
      'more' => ['password' => 'otherpassword']
    ];
    $stripped_data = Utils\stripSensitiveData($data, ['password']);
    $this->assertTrue($stripped_data['password'] == '*****');
    $this->assertTrue($stripped_data['key'] == 'value');
    $this->assertTrue($stripped_data['more']['password'] == '*****');
  }

}
