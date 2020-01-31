<?php

namespace Pantheon\Terminus\Plugins;

use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class PluginInfo
 * @package Pantheon\Terminus\Plugins
 */
class PluginInfo implements ConfigAwareInterface
{
    use ConfigAwareTrait;

    const MAX_COMMAND_DEPTH = 4;

    // Commands
    const GET_BRANCH_INSTALLED_VERSION_COMMAND = '[ -d %s ] && cd %s && git rev-parse --abbrev-ref HEAD';
    const GET_NONSTABLE_LATEST_VERSION_COMMAND =
        '[ -d %s ] && cd %s && git tag -l --sort=version:refname | grep %s | sort -r | xargs';
    const GET_STABLE_LATEST_VERSION_COMMAND =
        '[ -d %s ] && cd %s && git fetch --all && git tag -l --sort=version:refname | grep ^[v%s] | sort -r | head -1';
    const GET_TAGS_INSTALLED_VERSION_COMMAND = '[ -d %s ] && cd %s && git describe --tags 2> /dev/null';
    const VALIDATION_COMMAND = 'composer search -N -t terminus-plugin %s';

    // Installation Methods
    const COMPOSER_METHOD = 'composer';
    const GIT_METHOD = 'git';
    const UNKNOWN_METHOD = 'unknown';

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
     * PluginInfo constructor.
     * @param $plugin_dir
     */
    public function __construct($plugin_dir)
    {
        $this->plugin_dir = $plugin_dir;
        $this->info = $this->parsePluginInfo();
    }

    /**
     * Register an autoloader for the class files from the plugin itself
     * at plugin discovery time.  Note that the classes from libraries that
     * the plugin dependes on (from the `require` section of its composer.json)
     * are not available until one of its commands is called.
     *
     * @param Composer\Autoload\ClassLoader $loader
     */
    public function autoloadPlugin($loader)
    {
        if ($this->usesAutoload()) {
            $info = $this->getInfo();
            foreach ($info['autoload']['psr-4'] as $prefix => $path) {
                $loader->addPsr4($prefix, $this->getPath() . DIRECTORY_SEPARATOR . $path);
            }
        }
    }

    /**
     * Get all of the commands and hooks in the plugin.
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
        $command_files = $discovery->discover($path, $namespace);

        // If this plugin uses autoloading, then its autoloader will
        // have already been configured via autoloadPlugin(), below.
        // Otherwise, we will include all of its source files here.
        if (!$this->usesAutoload()) {
            $file_names = array_keys($command_files);
            foreach ($file_names as $file) {
                include $file;
            }
        }

        return $command_files;
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
     * @return array|null|string
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
        try {
            return $this->getTagInstalledVersion();
        } catch (TerminusNotFoundException $e) {
            try {
                return $this->getBranchInstalledVersion();
            } catch (TerminusNotFoundException $e) {
                return self::UNKNOWN_VERSION;
            }
        }
    }

    /**
     * Get the plugin installation method.
     *
     * @return string Plugin installation method
     */
    public function getInstallationMethod()
    {
        $git_dir = $this->getPath() . DIRECTORY_SEPARATOR . '.git';
        if (is_dir($git_dir)) {
            return self::GIT_METHOD;
        }
        $composer_json = $this->getPath() . DIRECTORY_SEPARATOR . 'composer.json';
        if (file_exists($composer_json)) {
            return self::COMPOSER_METHOD;
        }
        return self::UNKNOWN_METHOD;
    }

    /**
     * Get the latest available plugin version.
     *
     * @return string Latest plugin version
     */
    public function getLatestVersion()
    {
        try {
            return $this->getNonstableLatestVersion();
        } catch (TerminusNotFoundException $e) {
            try {
                return $this->getBranchInstalledVersion();
            } catch (TerminusNotFoundException $e) {
                return self::UNKNOWN_VERSION;
            }
        }
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
     * Checks for non-stable semantic version (ie. -beta1 or -rc2).
     * @return string The version number
     * @throws TerminusNotFoundException If the lookup of stable version fails
     */
    public function getNonstableLatestVersion()
    {
        $version = $this->getStableLatestVersion();
        preg_match(
            '/(v*.*)\-(.*)/',
            $version,
            $matches
        );

        exec(
            sprintf(
                self::GET_NONSTABLE_LATEST_VERSION_COMMAND,
                $this->getPath(),
                $this->getPath(),
                $this->getTerminusMajorVersion()
            ),
            $releases
        );

        if ((count($matches) > 1) && !empty($releases)) {
            foreach ($releases as $release) {
                // Update to stable release, if available.
                if ($release === $stable_version) {
                    return $release;
                }
            }
        }

        return $version;
    }

    /**
     * @return string
     */
    public function getPackagistURL()
    {
        return 'https://packagist.org/packages/'. $this->getName();
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
        preg_match('/.*\/(.*)/', $this->getName(), $matches);
        return $matches[1];
    }

    /**
     * @return string The version number
     * @throws TerminusNotFoundException If the lookup fails
     */
    public function getStableLatestVersion()
    {
        exec(
            sprintf(
                self::GET_STABLE_LATEST_VERSION_COMMAND,
                $this->getPath(),
                $this->getPath(),
                $this->getTerminusMajorVersion()
            ),
            $tag
        );
        $exception = new TerminusNotFoundException('Stable latest version not found.');
        if (empty($tag)) {
            throw $exception;
        }
        $version = array_pop($tag);
        if ($version === 'Fetching origin') {
            throw $exception;
        }
        return $version;
    }

    /**
     * Check whether a Packagist project is valid.
     *
     * @return bool True if valid, false otherwise
     */
    public function isValidPackagistProject()
    {
        // Search for the Packagist project.
        exec(sprintf(self::VALIDATION_COMMAND, $this->getName()), $items);
        if (empty($items)) {
            return false;
        }
        $item = array_shift($items);
        return ($item === $project);
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
     * Check to see if the provided info object has autoload info
     *
     * @param type $info
     * @return boolean
     */
    protected function hasAutoload($info)
    {
        return isset($info['autoload']) && isset($info['autoload']['psr-4']);
    }

    /**
     * Read and parse the info for the plugin.
     * Each check has an error message so that a plugin author gets the specific message needed if the plugin is malformed.
     *
     * @return array|string
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function parsePluginInfo()
    {
        if (!$this->plugin_dir) {
            throw new TerminusException('No plugin directory was specified');
        }
        if (!file_exists($this->plugin_dir)) {
            throw new TerminusException('The directory "{dir}" does not exist', ['dir' => $this->plugin_dir]);
        }
        if (!is_dir($this->plugin_dir)) {
            throw new TerminusException('The file "{dir}" is not a directory', ['dir' => $this->plugin_dir]);
        }
        if (!is_readable($this->plugin_dir)) {
            throw new TerminusException('The directory "{dir}" is not readable', ['dir' => $this->plugin_dir]);
        }

        $composer_json = $this->plugin_dir . '/composer.json';
        if (!file_exists($composer_json)) {
            throw new TerminusException('The file "{file}" does not exist', ['file' => $composer_json]);
        }
        if (!is_readable($composer_json)) {
            throw new TerminusException('The file "{file}" is not readable', ['file' => $composer_json]);
        }

        $info = json_decode(file_get_contents($composer_json), true);

        if (!$info) {
            throw new TerminusException('The file "{file}" does not contain valid JSON', ['file' => $composer_json]);
        }

        if (!isset($info['type']) || $info['type'] !== 'terminus-plugin') {
            throw new TerminusException(
                'The composer.json must contain a "type" attribute with the value "terminus-plugin"'
            );
        }

        if (!isset($info['extra']['terminus'])) {
            throw new TerminusException('The composer.json must contain a "terminus" section in "extras"');
        }

        if (!isset($info['extra']['terminus']['compatible-version'])) {
            throw new TerminusException(
                'The composer.json must contain a "compatible-version" field in "extras/terminus"'
            );
        }

        if ($this->hasAutoload($info)) {
            $namespaces = array_keys($info['autoload']['psr-4']);
            foreach ($namespaces as $namespace) {
                if (substr($namespace, -1) != '\\') {
                    throw new TerminusException(
                        'The namespace "{namespace}" in the composer.json autoload psr-4 section '
                        . 'must end with a namespace separator. Should be "{correct}"',
                        ['namespace' => addslashes($namespace), 'correct' => addslashes($namespace . '\\'),]
                    );
                }
            }
        }

        return (array)$info;
    }

    /**
     * Check to see if this plugin uses autloading
     * @return boolean
     */
    protected function usesAutoload()
    {
        return $this->hasAutoload($this->getInfo());
    }

    /**
     * @return string The version number
     * @throws TerminusNotFoundException If the lookup fails
     */
    private function getBranchInstalledVersion()
    {
        exec(
            sprintf(
                self::GET_BRANCH_INSTALLED_VERSION_COMMAND,
                $this->getPath(),
                $this->getPath()
            ),
            $branch
        );
        if (empty($branch)) {
            throw new TerminusNotFoundException('Installed branch version not found.');
        }
        $version = array_pop($branch);
        return $version;
    }

    /**
     * Return the directory where this plugin stores it's command files.
     *
     * @return string
     */
    private function getCommandFileDirectory()
    {
        $autoload = $this->getAutoloadInfo();
        return $this->getPath() . '/' . $autoload['dir'];
    }

    /**
     * @return string The version number
     * @throws TerminusNotFoundException If the lookup fails
     */
    private function getTagInstalledVersion()
    {
        exec(
            sprintf(
                self::GET_TAGS_INSTALLED_VERSION_COMMAND,
                $this->getPath(),
                $this->getPath()
            ),
            $tags
        );
        if (empty($tags)) {
            throw new TerminusNotFoundException('Installed tag version not found.');
        }
        $version = array_pop($tags);
        return $version;
    }

    /**
     * Get the Terminus major version.
     *
     * @return integer Terminus major version
     */
    private function getTerminusMajorVersion()
    {
        //preg_match('/(\d*).\d*.\d*/', $this->getConfig()->get('version'), $matches);
        preg_match('/(\d*).\d*.\d*/', '2.3.0', $matches);
        return $matches[1];
    }
}
