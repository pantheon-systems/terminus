<?php

namespace Pantheon\Terminus\Plugins;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\Hooks\InitializeHookInterface;
use Pantheon\Terminus\Exceptions\TerminusException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class PluginAutoloadDependencies
 *
 * Autoload the dependencies of a plugin -- libraries in the `require` section
 * of the plugin's composer.json file.
 *
 * Note that an autoloader for the plugin's classes itself is registered
 * by the PluginInfo class at plugin load time.
 */
class PluginAutoloadDependencies implements InitializeHookInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $src_dir;

    /**
     * Pass in the source directory where Terminus sources are located.
     */
    public function __construct($src_dir)
    {
        $this->src_dir = $src_dir;
    }

    /**
     * Called at the beginning of every command dispatch.
     * If this commandfile is a plugin, then search for its
     * autoload file and load it if necessary.
     */
    public function initialize(InputInterface $input, AnnotationData $annotation_data)
    {
        $autoload_file = $this->findAutoloadFile($annotation_data['_path']);
        if (!empty($autoload_file)) {
            include $autoload_file;
        }
    }

    /**
     * Given the path to the source file being loaded, return the
     * path to the autoload file to load.
     */
    protected function findAutoloadFile($path)
    {
        if (!$path) {
            return;
        }

        // If the commandfile path is inside Terminus, then
        // the autoload file has already been loaded.
        if ($this->pathIsInside($path, $this->src_dir)) {
            $this->logger->debug(
                'Plugin Autoload: %dir is a Terminus source file.',
                ['dir' => $path]
            );
            return;
        }

        // Find the plugin's base directory -- the one that
        // contains the composer.json file. Abort if we cannot
        // find a base directory for the plugin.
        $plugin_dir = $this->findPluginBaseDir($path);
        if (!$plugin_dir) {
            $this->logger->warning(
                'Plugin Autoload: Could not find the plugin base dir for %dir.',
                ['dir' => $path]
            );
            return;
        }

        // If there is no autoload file, then we might as well give up
        $autoload_file = $this->checkAutoloadPath($plugin_dir);
        if (!$autoload_file) {
            $this->logger->debug(
                'Plugin Autoload: %dir does not have an autoload file.',
                ['dir' => $plugin_dir]
            );
            return;
        }

        // If there is a composer.lock file here, then
        // validate that it is safe to load.
        $composer_validator = new ComposerDependencyValidator($this->src_dir);
        $composer_validator->validate($plugin_dir);

        return $autoload_file;
    }

    /**
     * Return 'true' if $path is contained anywhere inside
     * the provided $src_dir.
     */
    protected function pathIsInside($path, $src_dir)
    {
        return substr($path, 0, strlen($src_dir)) == $src_dir;
    }

    /**
     * Find the plugin's base directory -- the one that contains the
     * composer.json file.
     */
    protected function findPluginBaseDir($path)
    {
        // Walk up one directory. If we are already at the root,
        // then return.
        $check_dir = dirname($path);
        if ($check_dir == $path) {
            return;
        }

        // Also stop scanning if we reach the '.terminus' or 'plugins' directory.
        if ((basename($path) == '.terminus') || (basename($path) == 'plugins')) {
            return;
        }

        // If there is a 'composer.json' file here, then we are done.
        if (file_exists("$check_dir/composer.json")) {
            return $check_dir;
        }

        // Otherwise, keep scanning.
        return $this->findPluginBaseDir($check_dir);
    }

    /**
     * Return the path to the autoload file, relative to the
     * provided path, if it exists.
     */
    protected function checkAutoloadPath($path)
    {
        $autoload_file = "$path/vendor/autoload.php";
        if (file_exists($autoload_file)) {
            return $autoload_file;
        }
    }
}
