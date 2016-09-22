<?php

namespace Terminus\UnitTests\Models;

use Terminus\Collections\Sites;

/**
 * Testing class for Terminus\Models\Environment
 */
class EnvironmentTest extends \PHPUnit_Framework_TestCase
{

  /**
   * @var Sites
   */
    private $sites;

    public function setUp()
    {
        parent::setUp();
        $this->sites = new Sites();
    }

  /**
   * Ensure correct cacheserver connection info for environments
   *
   * @vcr site_connection-info
   */
    public function testCacheserverConnectionInfo()
    {
        logInWithBehatCredentials();
        $site = $this->sites->get('behat-tests');
        $env  = $site->environments->get('dev');

        $connection_info = $env->cacheserverConnectionInfo();

      // Cache server connection connection_info
        $pass = 'bf19fbfd2f584df591aa1c8666a8f126';
        $host = '23.253.39.24';
        $port = '11279';
        $user = "pantheon";

        $cache_info_expected = [
        'password' => $pass,
        'host'     => $host,
        'port'     => $port,
        'url'      => "redis://$user:$pass@$host:$port",
        'command'  => "redis-cli -h $host -p $port -a $pass",
        ];
        $this->assertArraySubset($cache_info_expected, $connection_info);

        setDummyCredentials();
    }

  /**
   * Ensure correct connection info for development environments
   * Development environment connection info includes git parameters
   *
   * @vcr site_connection-info
   */
    public function testConnectionInfoDev()
    {
        $this->$this->logInWithVCRCredentials();
        $site = $this->sites->get('behat-tests');
        $env  = $site->environments->get('dev');

        $connection_info = $env->connectionInfo();

        // SFTP Connection Info
        $sftp_user = "{$env->id}.{$site->id}";
        $sftp_host = "appserver.{$sftp_user}.drush.in";
        $sftp_port = "2222";

        $sftp_info_expected = [
        'sftp_username' => $sftp_user,
        'sftp_host' => $sftp_host,
        'sftp_password' => 'Use your account password',
        'sftp_url' => "sftp://{$env->id}.{$site->id}@{$sftp_host}:{$sftp_port}",
        'sftp_command' => "sftp -o Port={$sftp_port} {$env->id}.{$site->id}@{$sftp_host}",
        ];
        $this->assertArraySubset($sftp_info_expected, $connection_info);

        // Git Connection Info
        $git_user = "codeserver.{$env->id}.{$site->id}";
        $git_host = "{$git_user}.drush.in";
        $git_port = $sftp_port;
        $git_url  = "ssh://{$git_user}@{$git_host}:{$git_port}/~/repository.git";

        $git_info_expected = [
        'git_username' => $git_user,
        'git_host' => $git_host,
        'git_port' => $git_port,
        'git_url' => $git_url,
        'git_command' => "git clone {$git_url} {$site->get('name')}",
        ];
        $this->assertArraySubset($git_info_expected, $connection_info);

        // Database Connection Info
        $db_host  = "dbserver.{$env->id}.{$site->id}.drush.in";
        $db_user  = 'pantheon';
        $database = 'pantheon';
        $db_pass  = 'ad7e59695d264b3782c2a9fd959d6a40';
        $db_port  = '16698';

        $db_info_expected = [
        'mysql_host' => $db_host,
        'mysql_username' => $db_user,
        'mysql_password' => $db_pass,
        'mysql_port' => $db_port,
        'mysql_database' => $database,
        'mysql_url' => "mysql://{$db_user}:{$db_pass}@{$db_host}:{$db_port}/{$database}",
        'mysql_command' => "mysql -u $db_user -p$db_pass -h $db_host -P $db_port $database",
        ];
        $this->assertArraySubset($db_info_expected, $connection_info);

        // Cache server connection connection_info
        $cache_pass = 'bf19fbfd2f584df591aa1c8666a8f126';
        $cache_host = '23.253.39.24';
        $cache_port = '11279';
        $cache_user = "pantheon";

        $cache_info_expected = [
        'redis_password' => $cache_pass,
        'redis_host' => $cache_host,
        'redis_port' => $cache_port,
        'redis_url' => "redis://{$cache_user}:{$cache_pass}@{$cache_host}:{$cache_port}",
        'redis_command' => "redis-cli -h {$cache_host} -p {$cache_port} -a {$cache_pass}",
        ];
        $this->assertArraySubset($cache_info_expected, $connection_info);

        $this->setDummyCredentials();
    }

  /**
   * Ensure correct connection info for development environments
   * Non-Development environment connection info should not include git parameters
   *
   * @return void
   *
   * @vcr site_connection-info
   */
    public function testConnectionInfoNonDev()
    {
        $this->logInWithVCRCredentials();
        $site = $this->sites->get('behat-tests');
        $env  = $site->environments->get('live');

        $connection_info = $env->connectionInfo();

        // assert standard fields are present
        $this->assertArrayHasKey('sftp_command', $connection_info);
        $this->assertArrayHasKey('mysql_command', $connection_info);
        $this->assertArrayHasKey('redis_command', $connection_info);

        // assert that git connection info is not present
        $this->assertArrayNotHasKey('git_command', $connection_info);

        $this->setDummyCredentials();
    }

  /**
   * @vcr site_deploy
   */
    public function testCountDeployableCommits()
    {
        $this->logInWithVCRCredentials();
        $site     = $this->sites->get('behat-tests');
        $test_env = $site->environments->get('test');
        $this->assertEquals(4, $test_env->countDeployableCommits());
        $this->setDummyCredentials();
    }

  /**
   * @vcr site_deploy_no_changes
   */
    public function testCountNoDeployableCommits()
    {
        $this->logInWithVCRCredentials();
        $site     = $this->sites->get('behat-tests');
        $test_env = $site->environments->get('test');
        $this->assertEquals(0, $test_env->countDeployableCommits());
        $this->setDummyCredentials();
    }

  /**
   * Ensure correct database connection info for environments
   *
   * @vcr site_connection-info
   */
    public function testDatabaseConnectionInfo()
    {
        logInWithBehatCredentials();
        $site = $this->sites->get('behat-tests');
        $env  = $site->environments->get('dev');
        $connection_info = $env->databaseConnectionInfo();

      // Database Connection Info
        $host     = "dbserver.{$env->id}.{$site->id}.drush.in";
        $user     = 'pantheon';
        $database = 'pantheon';
        $pass     = 'ad7e59695d264b3782c2a9fd959d6a40';
        $port     = '16698';

        $db_info_expected = [
        'host' => $host,
        'username' => $user,
        'password' => $pass,
        'port' => $port,
        'database' => $database,
        'url' => "mysql://{$user}:{$pass}@{$host}:{$port}/{$database}",
        'command' => "mysql -u $user -p$pass -h $host -P $port $database",
        ];
        $this->assertArraySubset($db_info_expected, $connection_info);
        setDummyCredentials();
    }

  /**
   * @vcr site_deploy
   */
    public function testGetParentEnvironment()
    {
        logInWithBehatCredentials();
        $site     = $this->sites->get('behat-tests');
        $test_env = $site->environments->get('test');
        $dev_env  = $test_env->getParentEnvironment();
        $this->assertEquals($dev_env->get('id'), 'dev');
        setDummyCredentials();
    }

  /**
   * Ensure correct Git connection info for environments
   *
   * @vcr site_connection-info
   */
    public function testGitConnectionInfo()
    {
        logInWithBehatCredentials();
        $site = $this->sites->get('behat-tests');
        $env  = $site->environments->get('dev');
        $connection_info = $env->gitConnectionInfo();

      // Git Connection Info
        $user = "codeserver.{$env->id}.{$site->id}";
        $host = "$user.drush.in";
        $port = '2222';
        $url  = "ssh://$user@$host:$port/~/repository.git";

        $git_info_expected = [
        'username' => $user,
        'host' => $host,
        'port' => $port,
        'url' => $url,
        'command' => "git clone $url {$site->get('name')}",
        ];
        $this->assertArraySubset($git_info_expected, $connection_info);
        setDummyCredentials();
    }

  /**
   * @vcr site_deploy
   */
    public function testHasDeployableCode()
    {
        $this->logInWithVCRCredentials();
        $site     = $this->sites->get('behat-tests');
        $test_env = $site->environments->get('test');
        $this->assertTrue($test_env->hasDeployableCode());
        $this->setDummyCredentials();
    }

  /**
   * @vcr site_deploy_no_changes
   */
    public function testHasNoDeployableCode()
    {
        $this->logInWithVCRCredentials();
        $site     = $this->sites->get('behat-tests');
        $test_env = $site->environments->get('test');
        $this->assertFalse($test_env->hasDeployableCode());
        $this->setDummyCredentials();
    }

  /**
   * Ensure correct SFTP connection info for environments
   *
   * @vcr site_connection-info
   */
    public function testSftpConnectionInfo()
    {
        logInWithBehatCredentials();
        $site = $this->sites->get('behat-tests');
        $env  = $site->environments->get('dev');
        $connection_info = $env->sftpConnectionInfo();

      // SFTP Connection Info
        $user = "{$env->id}.{$site->id}";
        $host = "appserver.{$user}.drush.in";
        $port = "2222";

        $sftp_info_expected = [
        'username' => $user,
        'host' => $host,
        'password' => 'Use your account password',
        'url' => "sftp://{$env->id}.{$site->id}@$host:$port",
        'command' => "sftp -o Port=$port {$env->id}.{$site->id}@$host",
        ];
        $this->assertArraySubset($sftp_info_expected, $connection_info);
        setDummyCredentials();
    }
}
