<?php

namespace Pantheon\Terminus\Plugins;

use Composer\Semver\Semver;
use Consolidation\AnnotatedCommand\CommandFileDiscovery;
use Pantheon\Terminus\Exceptions\TerminusException;

class PluginInfo
{
    const MAX_COMMAND_DEPTH = 4;

    /**
     * @var string
     */
    protected $plugin_dir;

    /**
     * @var null|array
     */
    protected $info = null;

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
     * Get the info array for the plugin.
     *
     * @return array|null|string
     */
    public function getInfo()
    {
        return $this->info;
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
        $discovery->setSearchPattern('*Command.php')->setSearchLocations([])->setSearchDepth(self::MAX_COMMAND_DEPTH);
        $command_files = $discovery->discover($path, $namespace);

        // @TODO: Decide if we should require an autoloader for plugins or just include the command files here.
        $file_names = array_keys($command_files);
        foreach ($file_names as $file) {
            include $file;
        }

        return $command_files;
    }

    /**
     * Read and parse the info for the plugin.
     *
     * @return array|string
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function parsePluginInfo()
    {
        // Each of these checks is broken out so that a plugin author can get specific error message if the plugin is malformed.
        if (!$this->plugin_dir) {
            throw new TerminusException('No plugin directory was specified');
        }
        if (!file_exists($this->plugin_dir)) {
            throw new TerminusException('The directory "{dir}" is does not exist', ['dir' => $this->plugin_dir]);
        }
        if (!is_dir($this->plugin_dir)) {
            throw new TerminusException('The file "{dir}" is not a directory', ['dir' => $this->plugin_dir]);
        }
        if (!is_readable($this->plugin_dir)) {
            throw new TerminusException('The directory "{dir}" is not readable', ['dir' => $this->plugin_dir]);
        }

        $composer_json = $this->plugin_dir . '/composer.json';
        if (!file_exists($composer_json)) {
            throw new TerminusException('The file "{file}" is does not exist', ['file' => $composer_json]);
        }
        if (!is_readable($composer_json)) {
            throw new TerminusException('The file "{file}" is not readable', ['file' => $composer_json]);
        }

        $info = json_decode(file_get_contents($composer_json), true);

        if (!$info) {
            throw new TerminusException('The file "{file}" does not contain valid JSON', ['file' => $composer_json]);
        }

        if (!isset($info['type']) || $info['type'] !== 'terminus-plugin') {
            throw new TerminusException('The composer.json must contain a "type" attribute with the value "terminus-plugin"');
        }

        if (!isset($info['extra']['terminus'])) {
            throw new TerminusException('The composer.json must contain a "terminus" section in "extras"');
        }

        if (!isset($info['extra']['terminus']['compatible-version'])) {
            throw new TerminusException('The composer.json must contain a "compatible-version" field in "extras/terminus"');
        }

        if (isset($info['autoload']) && isset($info['autoload']['psr-4'])) {
            $namespaces = array_keys($info['autoload']['psr-4']);
            foreach ($namespaces as $namespace) {
                if (substr($namespace, -1) != '\\') {
                    $correctNamespace = $namespace . '\\';
                    throw new TerminusException('The namespace "{namespace}" in the composer.json autoload psr-4 section must end with a namespace separator. Should be "{correct}"', ['namespace' => addslashes($namespace), 'correct' => addslashes($correctNamespace)]);
                }
            }
        }

        return (array)$info;
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

    public function getName()
    {
        $info = $this->getInfo();
        if (isset($info['name'])) {
            return $info['name'];
        }
        return basename($this->plugin_dir);
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
     * Return the directory where this plugin stores it's command files.
     *
     * @return string
     */
    private function getCommandFileDirectory()
    {
        $autoload = $this->getAutoloadInfo();
        return $this->plugin_dir . '/' . $autoload['dir'];
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
}
