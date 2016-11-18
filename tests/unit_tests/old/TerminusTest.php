<?php

namespace Terminus\UnitTests;

use Symfony\Component\Yaml\Yaml;
use Pantheon\Terminus\Config;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Auth;
use Pantheon\Terminus\Loggers\Logger;
use Pantheon\Terminus\Runner;
use Pantheon\Terminus\Session;

/**
 * A base file for 0.x unit tests
 */
abstract class TerminusTest extends \PHPUnit_Framework_TestCase
{

  /**
   * @var Config
   */
    protected $config;
  /**
   * @var string
   */
    protected $log_file_name;
  /**
   * @var Runner
   */
    protected $runner;

  /**
   * @inheritdoc
   */
    public function setUp()
    {
        parent::setUp();
        $this->config = new Config();
        $this->runner = new Runner(['debug' => true,]);
        $this->log_file_name = $this->config->get('log_dir') . 'log_' . date('Y-m-d') . '.txt';
    }

  /**
  * Returns the username and password for Behat fixtures
  *
  * @return string[]
  */
    public function getVCRCredentials()
    {
        $vcr_config = Yaml::parse(file_get_contents($this->config->get('root') . '/tests/config/behat.yml'));
        return $vcr_config['default']['suites']['default']['contexts'][0]['Terminus\FeatureTests\FeatureContext']['parameters'];
    }

  /**
  * Logs in with Behad credentials to enable Behat fixture use
  *
  * @return void
  */
    public function logInWithVCRCredentials()
    {
        $creds = $this->getVCRCredentials();
        $creds['token'] = $creds['machine_token'];
        $auth = new Auth();
        $auth->logInViaMachineToken($creds);
    }

  /**
  * Removes the named file and replaces it with the previously moved file
  *
  * @param string $file_name Name of the file to remove and replace
  * @return void
  */
    public function resetOutputDestination($file_name)
    {
        $moved_file_suffix = '.testmoved';
        if (file_exists($file_name)) {
            exec("rm -r $file_name");
        }
        if (file_exists($file_name.$moved_file_suffix)) {
            exec("mv $file_name.$moved_file_suffix $file_name");
        }
    }

  /**
  * Retrieves the content of the named file
  *
  * @param string $file_name Name of the file to retrieve the contents of
  * @return string
  */
    public function retrieveOutput($file_name = '/tmp/output')
    {
        if (!file_exists($file_name)) {
            throw new TerminusException('File "{file}" does not exist.', ['file' => $file_name,]);
        }
        $output = file_get_contents($file_name);
        return $output;
    }

  /**
  * Moves the file of this name and creates a new file with the same name
  *
  * @param string $file_name Name of the file to remove and create
  * @return void
  */
    public function setOutputDestination($file_name)
    {
        $moved_file_suffix = '.testmoved';
        if (file_exists($file_name)) {
            exec("mv $file_name $file_name.$moved_file_suffix");
        }
        exec("touch $file_name");
    }

  /**
  * Sets some dummy credentials for this test run
  *
  * @return void
  */
    public function setDummyCredentials()
    {
        $session_id  = '0ffec038-4410-43d0-a404-46997f672d7a%3A68486878';
        $session_id .= '-dd87-11e4-b243-bc764e1113b5%3AbQR2fyNMh5PQXN6F2Ewge';
        // Set some dummy credentials
        Session::setData(
            (object)[
              'user_id' => '0ffec038-4410-43d0-a404-46997f672d7a',
              'session' => $session_id,
              'session_expire_time' => strtotime('+8 days'),
              'email' => 'bensheldon+pantheontest@gmail.com',
            ]
        );
    }
}
