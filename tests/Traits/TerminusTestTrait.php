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
        $project_dir = dirname(dirname(__DIR__));
        exec(
            sprintf("%s/%s %s", $project_dir, TERMINUE_BIN_FILE, $command),
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
     * @param int|null $expected_status
     *   Status code. Null = no status check
     */
    protected function terminus(string $command, ?int $expected_status = 0): ?string
    {
        [$output, $status] = static::callTerminus($command);
        if ($expected_status !== null) {
            $this->assertEquals($expected_status, $status, $output);
        }
        if (is_array($output)) {
            join("", $output);
        }
        return $output;
    }

    /**
     * @param $command
     * @param int|null $expected_status
     *
     * @return array|string|null
     */
    protected function terminusJsonResponse($command, ?int $expected_status = 0)
    {
        $response = trim($this->terminus(
            $command . " --format=json",
            $expected_status
        ));
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
     * Returns the site name.
     *
     * @return string
     */
    protected function getSiteName(): string
    {
        return getenv('TERMINUS_SITE');
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
        return getenv('TERMINUS_DEPENDENCIES_BASE_DIR');
    }

    /**
     * Remove given dir.
     *
     * @param string $dir The dir to remove
     *
     * @return array
     *   The execution's output and status.
     */
    protected static function removeDir(string $dir): array
    {
        if (is_dir($dir)) {
            $project_dir = dirname(dirname(__DIR__));
            exec(
                sprintf("rm -r %s", $dir),
                $output,
                $status
            );
            $output = implode("\n", $output);

            return [$output, $status];
        }
        return ['', 0];
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
     * Returns the site info.
     *
     * @return array
     */
    private function getSiteInfo(): array
    {
        static $site_info;
        if (is_null($site_info)) {
            $site_info = $this->terminusJsonResponse(sprintf('site:info %s', $this->getSiteName()));
        }

        return $site_info;
    }
}
