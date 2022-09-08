<?php

namespace Pantheon\Terminus\Plugins;

use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Composer\Semver\Semver;

/**
 * Class PluginInfo.
 *
 * @package Pantheon\Terminus\Plugins
 */
class PluginInfo implements ConfigAwareInterface, ContainerAwareInterface, LoggerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use LoggerAwareTrait;

    const MAX_COMMAND_DEPTH = 4;

    // Commands
    const GET_LATEST_AVAILABLE_VERSION = 'composer show -d {dir} {package} --latest --all --format=json';
    const VALIDATION_COMMAND = 'composer search -N -t terminus-plugin {project}';

    // Version Numbers
    const UNKNOWN_VERSION = 'unknown';

    /**
     * @var null|array
     */

    protected $info = null;
    /**
     * @var string
     */

    protected $plugin_dir;
    /**
     * @var string
     */
    protected $stable_latest_version;

    /**
     * Determines whether current terminus version satisfies given terminus-compatible value.
     */
    public function isVersionCompatible($plugin_compatible = null)
    {
        if (!$plugin_compatible) {
            $plugin_compatible = $this->getCompatibleTerminusVersion();
        }
        $current_version = $this->getConfig()->get('version');
        $fallback_version = $this->getConfig()->get('plugins_fallback_compatibility');
        return (Semver::satisfies($current_version, $plugin_compatible) ||
            Semver::satisfies($fallback_version, $plugin_compatible));
    }

    /**
     * Set packageinfo.
     */
    public function setInfoArray($info)
    {
        $this->info = $info;
        $dependencies_dir = $this->getConfig()->get('terminus_dependencies_dir');
        $this->plugin_dir = $dependencies_dir . '/vendor/' . $info['name'];
    }

    /**
     * Get all the commands and hooks in the plugin.
     *
     * @return array
     */
    public function getCommandsAndHooks()
    {
        $path = $this->getCommandFileDirectory();
        $namespace = $this->getCommandNamespace();
        $discovery = new CommandFileDiscovery();
        $discovery->setSearchPattern('/.*(Command|Hook).php$/')
            ->setSearchLocations([])
            ->setSearchDepth(self::MAX_COMMAND_DEPTH);

        return $discovery->discover($path, $namespace);
    }

    /**
     * Get the compatible Terminus version.
     *
     * @return string A version constraint string defining what versions of Terminus this plugin works with.
     */
    public function getCompatibleTerminusVersion()
    {
        return $this->getInfo()['extra']['terminus']['compatible-version'];
    }

    /**
     * Get the info array for the plugin.
     *
     * @return array|null
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Get the currently installed plugin version.
     *
     * @return string Installed plugin version
     */
    public function getInstalledVersion()
    {
        if (!empty($this->info['version'])) {
            return $this->info['version'];
        }
        $dependencies_dir = $this->getConfig()->get('terminus_dependencies_dir');
        $composer_lock = json_decode(
            file_get_contents($dependencies_dir . '/composer.lock'),
            true,
            10
        );
        foreach ($composer_lock['packages'] as $package) {
            if ($package['name'] === $this->getName()) {
                return $package['version'];
            }
        }
        return self::UNKNOWN_VERSION;
    }

    /**
     * Get the latest available plugin version.
     *
     * @return string Latest plugin version
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function getLatestVersion()
    {
        $command = str_replace(
            '{package}',
            $this->getName() ?? '',
            self::GET_LATEST_AVAILABLE_VERSION
        );
        $command = $this->populateComposerWorkingDir($command);

        $results = $this->runCommand($command);
        if (!empty($results['output'])) {
            $package_info = json_decode($results['output'], true, 10);
            if (empty($package_info)) {
                return 'n/a';
            }
            if (!empty($package_info['latest'])) {
                return $package_info['latest'];
            }
            $versions = $package_info['versions'];
            return reset($versions);
        }
        return self::UNKNOWN_VERSION;
    }

    /**
     * @return string
     */
    public function getName()
    {
        $info = $this->getInfo();
        if (isset($info['name'])) {
            return $info['name'];
        }
        return basename($this->getPath());
    }

    /**
     * @return string Location of the plugin installation
     */
    public function getPath()
    {
        return $this->plugin_dir;
    }

    /**
     * @return string
     */
    public function getPluginName()
    {
        return self::getPluginNameFromProjectName($this->getName());
    }

    /**
     * Check whether a Packagist project is valid.
     *
     * @return bool True if valid, false otherwise
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function isValidPackagistProject()
    {
        return self::checkWhetherPackagistProject($this->getName(), $this->getLocalMachine());
    }

    /**
     * Check whether a Packagist project is valid.
     *
     * @param string $project_name Name of plugin package to install
     * @param LocalMachineHelper $local_machine_helper
     *
     * @return bool True if valid, false otherwise
     */
    public static function checkWhetherPackagistProject($project_name, LocalMachineHelper $local_machine_helper)
    {
        // Separate version if exists.
        $project_name_parts = explode(':', $project_name);
        $project_name = reset($project_name_parts);
        // Search for the Packagist project.
        $command = str_replace(
            '{project}',
            $project_name ?? '',
            self::VALIDATION_COMMAND
        );
        $results = $local_machine_helper->exec($command);
        $result = trim($results['output']);

        if (empty($result)) {
            return false;
        }
        return ($result === $project_name);
    }

    /**
     * @param $version_number
     * @return string
     */
    public static function getMajorVersionFromVersion($version_number)
    {
        preg_match('/(\d*).\d*.\d*/', $version_number, $version_matches);
        return $version_matches[1];
    }

    /**
     * @return string
     */
    public static function getPluginNameFromProjectName($project_name)
    {
        preg_match('/.*\/(.*)/', $project_name, $matches);
        return $matches[1] ?? 'n/a';
    }

    /**
     * Get the PSR-4 autoload info from the composer.json if any.
     *
     * @return array
     */
    protected function getAutoloadInfo()
    {
        $info = $this->getInfo();
        if (isset($info['autoload']['psr-4'])) {
            $keys = array_keys($info['autoload']['psr-4']);
            return [
                'prefix' => reset($keys),
                'dir' => reset($info['autoload']['psr-4'])
            ];
        }
        return ['prefix' => '', 'dir' => 'src'];
    }

    /**
     * Return the namespace for this plugin's commands and hooks.
     *
     * @return string
     */
    protected function getCommandNamespace()
    {
        $autoload = $this->getAutoloadInfo();
        return $autoload['prefix'];
    }

    /**
     * @return LocalMachineHelper
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function getLocalMachine()
    {
        return $this->getContainer()->get(LocalMachineHelper::class);
    }

    /**
     * Return the directory where this plugin stores its command files.
     *
     * @return string
     */
    private function getCommandFileDirectory()
    {
        $autoload = $this->getAutoloadInfo();
        return $this->getPath() . '/' . $autoload['dir'];
    }

    /**
     * @param string $command
     *
     * @return array
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function runCommand(string $command)
    {
        $this->logger->debug('Running {command}...', compact('command'));
        $results = $this->getLocalMachine()->exec($command);
        $this->logger->debug("Returned:\n{output}", $results);
        return $results;
    }

    /**
     * Replaces "{dir}" placeholder (Composer's "-d" option) in the command string with the actual plugin directory.
     *
     * @param string $command
     *
     * @return string
     */
    private function populateComposerWorkingDir(string $command): string
    {
        return str_replace(
            '{dir}',
            $this->plugin_dir,
            $command
        );
    }
}
