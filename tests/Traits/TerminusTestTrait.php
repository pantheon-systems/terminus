<?php

namespace Pantheon\Terminus\Tests\Traits;

/**
 * Trait TerminusTestTrait
 *
 * @package Pantheon\Terminus\Tests\Traits
 */
trait TerminusTestTrait
{
    /**
     * Run a terminus command.
     *
     * @param string $command The command to run
     *
     * @return array
     *   The execution's output and status.
     */
    protected static function callTerminus(string $command): array
    {
        $project_dir = dirname(__DIR__, 2);
        exec(
            sprintf("%s/%s %s", $project_dir, TERMINUS_BIN_FILE, $command),
            $output,
            $status
        );
        $output = implode("\n", $output);

        return [$output, $status];
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
        if ($suffixParts > 0) {
            $command = sprintf('%s --yes %s', $command, implode(' ', $suffixParts));
        } else {
            $command = sprintf('%s --yes', $command);
        }

        [$output, $status] = static::callTerminus($command);
        if (true === $assertExitCode) {
            $this->assertEquals(0, $status, $output);
        }

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
     * Returns the site name.
     *
     * @return string
     */
    public static function getSiteName(): string
    {
        return getenv('TERMINUS_SITE');
    }

    /**
     * Returns the organization ID.
     *
     * @return string
     */
    protected function getOrg(): string
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
     * Returns the dependencies base dir.
     *
     * @return string
     */
    protected function getDependenciesBaseDir(): string
    {
        global $terminusPluginsDependenciesVersion;

        return sprintf(
            '%s-%s',
            getenv('TERMINUS_DEPENDENCIES_BASE_DIR'),
            $terminusPluginsDependenciesVersion
        );
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
}
