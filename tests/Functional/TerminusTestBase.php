<?php

namespace Pantheon\Terminus\Tests\Functional;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;

/**
 * Class TerminusTestBase.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
abstract class TerminusTestBase extends TestCase
{

    /**
     * @var \Monolog\Logger $logger
     */
    protected Logger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $GLOBALS['LOGGER'];
    }

    /**
     * Run a terminus command.
     *
     * @param string $command
     *   The command to run.
     * @param string|null $pipeInput
     *   The pipe input.
     *
     * @return array
     *   The execution's stdout [0], exit code [1] and stderr [2].
     */
    protected static function callTerminus(string $command, ?string $pipeInput = null): array
    {
        $procCommand = sprintf('%s %s', TERMINUS_BIN_FILE, $command);
        if (null !== $pipeInput) {
            $procCommand = sprintf('%s | %s', $pipeInput, $procCommand);
        }

        $process = proc_open(
            $procCommand,
            [
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            dirname(__DIR__, 2)
        );

        if (!is_resource($process)) {
            return ['', 1, sprintf('Failed executing command "%s"', $procCommand)];
        }

        $stdout = trim(stream_get_contents($pipes[1]));
        fclose($pipes[1]);

        $stderr = trim(stream_get_contents($pipes[2]));
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [$stdout, $exitCode, $stderr];
    }

    /**
     * Run a terminus command.
     *
     * @param string $command
     *   The command to run.
     * @param array $suffixParts
     *   Additional command options added to the end of the command line.
     * @param bool $assertExitCode
     *   If set to TRUE, assert the exit code equals to zero.
     */
    protected function terminus(string $command, array $suffixParts = [], bool $assertExitCode = true): ?string
    {
        if (count($suffixParts) > 0) {
            $command = sprintf('%s --yes %s', $command, implode(' ', $suffixParts));
        } else {
            $command = sprintf('%s --yes', $command);
        }

        [$output, $exitCode, $error] = static::callTerminus($command);
        if (true === $assertExitCode) {
            $this->assertEquals(0, $exitCode, $error);
        }

        $this->assertStringNotContainsString(
            'PHP Deprecated',
            $output,
            'Command output must not contain PHP deprecation notices'
        );
        $this->assertStringNotContainsString(
            'PHP Deprecated',
            $error,
            'Command error must not contain PHP deprecation notices'
        );

        return $output;
    }

    /**
     * Run a terminus command with the pipe input.
     *
     * @param string $command
     *   The command to run.
     * @param string $pipeInput
     *   The pipe input.
     */
    protected function terminusPipeInput(string $command, string $pipeInput)
    {
        $command = sprintf('%s --yes', $command);

        [$output, $status] = static::callTerminus($command, $pipeInput);
        $this->assertEquals(0, $status, $output);

        return $output;
    }

    /**
     * Run a terminus command redirecting stderr to stdout.
     *
     * @param string $command
     *   The command to run.
     */
    protected function terminusWithStderrRedirected(string $command): ?string
    {
        return $this->terminus($command, ['2>&1']);
    }

    /**
     * @param $command
     *
     * @return array|string|null
     */
    protected function terminusJsonResponse($command)
    {
        $response = trim($this->terminus($command, ['--format=json']));
        try {
            return json_decode(
                $response,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $jsonException) {
            return $response;
        }
    }

    /**
     * Asserts terminus command execution result is equal to the expected in multiple attempts.
     *
     * @param callable $callable
     *   The callable which provides the actual terminus command execution result.
     * @param mixed $expected
     *   The expected result.
     * @param int $attempts
     *   The maximum number of attempts.
     * @param int $intervalSeconds
     *   The interval between attempts in seconds.
     */
    protected function assertTerminusCommandResultEqualsInAttempts(
        callable $callable,
        $expected,
        int $attempts = 24,
        int $intervalSeconds = 10
    ): void {
        do {
            $actual = $callable();
            if ($actual === $expected) {
                break;
            }

            sleep($intervalSeconds);
            $attempts--;
        } while ($attempts > 0);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Asserts terminus command execution succeeds in multiple attempts.
     *
     * @param string $command
     *   The command to execute.
     * @param int $attempts
     *   The maximum number of attempts.
     */
    protected function assertTerminusCommandSucceedsInAttempts(string $command, int $attempts = 3): void
    {
        $this->assertTerminusCommandResultEqualsInAttempts(
            fn() => static::callTerminus(sprintf('%s --yes', $command))[1],
            0,
            $attempts
        );
    }

    /**
     * Returns the site name.
     *
     * @param string $
     *
     * @return string
     */
    public static function getSiteName(string $siteFramework = "drupal"): string
    {
        switch ($siteFramework) {
            case "wordpress":
                return getenv('TERMINUS_SITE_WP');
            case "wordpress_network":
                return getenv('TERMINUS_SITE_WP_NETWORK');
            default:
                return getenv('TERMINUS_SITE');
        }
    }

    /**
     * Returns the organization ID.
     *
     * @return string
     */
    protected static function getOrg(): string
    {
        return getenv('TERMINUS_ORG');
    }

    /**
     * Returns the plugin dir.
     *
     * @return string
     */
    protected function getPluginsDir(): string
    {
        return getenv('TERMINUS_PLUGINS_DIR');
    }

    /**
     * Returns the Terminus 2 plugin dir.
     *
     * @return string
     */
    protected function getPlugins2Dir(): string
    {
        return getenv('TERMINUS_PLUGINS2_DIR');
    }

    /**
     * Returns the Terminus base dir.
     *
     * @return string
     */
    protected function getBaseDir(): string
    {
        return getenv('TERMINUS_BASE_DIR');
    }

    /**
     * Returns the dependencies base dir.
     *
     * @return string
     */
    protected function getDependenciesBaseDir(): string
    {
        return getenv('TERMINUS_DEPENDENCIES_BASE_DIR');
    }

    /**
     * Returns TRUE if the test site is based on Drupal framework.
     *
     * @return bool
     *
     * @throws \Exception
     */
    protected function isSiteFrameworkDrupal(): bool
    {
        switch ($this->getSiteFramework()) {
            case 'drupal':
            case 'drupal8':
                return true;
            default:
                return false;
        }
    }

    /**
     * Returns the test site framework.
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getSiteFramework(): string
    {
        $site_info = $this->getSiteInfo();

        if (!isset($site_info['framework'])) {
            throw new \Exception(sprintf('Failed to get framework for test site %s', $this->getSiteName()));
        }

        return $site_info['framework'];
    }

    /**
     * Returns the test site id.
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function getSiteId(): string
    {
        $site_info = $this->getSiteInfo();

        if (!isset($site_info['id'])) {
            throw new \Exception(sprintf('Failed to get id for test site %s', $this->getSiteName()));
        }

        return $site_info['id'];
    }

    /**
     * Returns the test user email address.
     *
     * @return string
     */
    protected function getUserEmail(): string
    {
        return getenv('TERMINUS_USER');
    }

    /**
     * Returns TRUE for a CI environment.
     *
     * @return bool
     */
    protected function isCiEnv(): bool
    {
        return (bool) getenv('CI');
    }

    /**
     * Returns the site info.
     *
     * @return array
     */
    protected function getSiteInfo(): array
    {
        static $site_info;
        if (is_null($site_info)) {
            $site_info = $this->terminusJsonResponse(sprintf('site:info %s', $this->getSiteName()));
        }

        return $site_info;
    }

    /**
     * Returns the testing runtime multidev name.
     *
     * @return string
     */
    protected static function getMdEnv(): string
    {
        return getenv('TERMINUS_TESTING_RUNTIME_ENV');
    }

    /**
     * Sets the testing runtime multidev name.
     */
    public static function setMdEnv(string $name): void
    {
        putenv(sprintf('TERMINUS_TESTING_RUNTIME_ENV=%s', $name));
    }

    /**
     * Returns site and environment in a form of "<site>.<env>" string which used in most commands.
     *
     * @return string
     */
    protected function getSiteEnv(): string
    {
        return sprintf('%s.%s', $this->getSiteName(), $this->getMdEnv());
    }

    /**
     * Returns the absolute path to the local test site directory.
     *
     * @return string
     */
    protected function getLocalTestSiteDir(): string
    {
        return implode(DIRECTORY_SEPARATOR, [$_SERVER['HOME'], 'pantheon-local-copies', self::getSiteName()]);
    }

    /**
     * Generates and uploads a test file to the site.
     *
     * @param string $siteEnv
     * @param string $filePath
     *
     * @return string
     *   The name of the test file.
     */
    protected function uploadTestFileToSite(string $siteEnv, string $filePath): string
    {
        if (!extension_loaded('ssh2')) {
            $this->markTestSkipped(
                'PECL SSH2 extension for PHP is required.'
            );
        }

        // Get SFTP connection information.
        $siteInfo = $this->terminusJsonResponse(
            sprintf('connection:info %s --fields=sftp_username,sftp_host', $siteEnv)
        );
        $this->assertNotEmpty($siteInfo);
        $this->assertTrue(
            isset($siteInfo['sftp_username'], $siteInfo['sftp_host']),
            'SFTP connection info should contain "sftp_username" and "sftp_host" values.'
        );

        // Upload a test file to the server.
        $session = ssh2_connect(
            $siteInfo['sftp_host'],
            2222
        );
        $this->assertTrue(
            ssh2_auth_agent($session, $siteInfo['sftp_username']),
            'Failed to authenticate over SSH using the ssh agent'
        );
        $sftp = ssh2_sftp($session);
        $this->assertNotFalse($sftp);

        $uniqueId = md5(mt_rand());
        $fileName = sprintf('terminus-functional-test-file-%s.txt', $uniqueId);
        $stream = fopen(
            sprintf('ssh2.sftp://%d/%s/%s', intval($sftp), $filePath, $fileName),
            'w'
        );
        $this->assertNotFalse($stream, 'Failed to open a test file for writing');
        $this->assertNotFalse(
            fwrite(
                $stream,
                sprintf('This is a test file (%s) to use in Terminus functional testing assertions.', $uniqueId)
            ),
            'Failed to write a test file'
        );
        fclose($stream);

        return $fileName;
    }

    /**
     * Asserts the command exists.
     *
     * @param string $commandName
     *   The command name to assert.
     */
    protected function assertCommandExists(string $commandName)
    {
        $commandList = $this->terminus('list');
        $this->assertStringContainsString($commandName, $commandList);
    }

    /**
     * Asserts the command does not exist.
     *
     * @param string $commandName
     *   The command name to assert.
     */
    protected function assertCommandDoesNotExist(string $commandName)
    {
        $commandList = $this->terminus('list');
        $this->assertStringNotContainsString($commandName, $commandList);
    }
}
