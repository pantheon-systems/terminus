<?php

namespace Pantheon\Terminus\Plugins;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\Hooks\InitializeHookInterface;
use Pantheon\Terminus\Exceptions\TerminusException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class PluginAutoload
 */
class PluginAutoload implements InitializeHookInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Called at the beginning of every command dispatch.
     * If this commandfile is a plugin, then search for its
     * autoload file and load it if necessary.
     */
    public function initialize(InputInterface $input, AnnotationData $annotationData)
    {
        $autoloadFile = $this->findAutoloadFile($annotationData['_path']);
        if (!empty($autoloadFile)) {
            include $autoloadFile;
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
        $terminusSrcDir = $this->findTerminusSrcDir();
        if (!$terminusSrcDir) {
            $this->logger->debug(
                'Plugin Autoload: Could not find Terminus source directory.'
            );
            return;
        }

        // If the commandfile path is inside Terminus, then
        // the autoload file has already been loaded.
        if ($this->pathInside($path, $terminusSrcDir)) {
            $this->logger->debug(
                'Plugin Autoload: %dir is a Terminus source file.',
                ['dir' => $path]
            );
            return;
        }

        // Find the plugin's base directory -- the one that
        // contains the composer.json file. Abort if we cannot
        // find a base directory for the plugin.
        $pluginBaseDir = $this->findPluginBaseDir($path);
        if (!$pluginBaseDir) {
            $this->logger->warning(
                'Plugin Autoload: Could not find the plugin base dir for %dir.',
                ['dir' => $path]
            );
            return;
        }

        // If there is no autoload file, then we might as well give up
        $autoloadFile = $this->checkAutoloadPath($pluginBaseDir);
        if (!$autoloadFile) {
            // TODO: Maybe we should give a warning if there IS a composer.lock,
            // but there is NOT an autoload file, so that we can tell the
            // user to run 'composer install' (or do it for them).
            // We don't support the composer.lock
            // at this point anyway. It might be better to have the plugin
            // manager take care of this at install time. This will happen
            // automatically if installing via 'composer create-project'.
            $this->logger->debug(
                'Plugin Autoload: %dir does not have an autoload file.',
                ['dir' => $pluginBaseDir]
            );
            return;
        }

        // If there is a composer.lock file here, then
        // validate that it is safe to load.
        $this->validateComposerLock($pluginBaseDir, $terminusSrcDir);

        return $autoloadFile;
    }

    /**
     * Determine whether the provided path is inside Terminus itself.
     */
    protected function findTerminusSrcDir()
    {
        // The Terminus class is located at the root of our 'src'
        // directory. Get the path to the class to determine
        // whether or not the path we are testing is inside this
        // same directory.
        $terminusClass = new \ReflectionClass(\Pantheon\Terminus\Terminus::class);
        return dirname($terminusClass->getFileName());
    }

    protected function pathInside($path, $terminusSrcDir)
    {
        return substr($path, 0, strlen($terminusSrcDir)) == $terminusSrcDir;
    }

    protected function findPluginBaseDir($path)
    {
        // Walk up one directory. If we are already at the root,
        // then return.
        $checkDir = dirname($path);
        if ($checkDir == $path) {
            return;
        }

        // Also stop scanning if we reach the '.terminus' or 'plugins' directory.
        if ((basename($path) == '.terminus') || (basename($path) == 'plugins')) {
            return;
        }

        // If there is a 'composer.json' file here, then we are done.
        if (file_exists("$checkDir/composer.json")) {
            return $checkDir;
        }

        // Otherwise, keep scanning.
        return $this->findPluginBaseDir($checkDir);
    }

    protected function validateComposerLock($pluginBaseDir, $terminusSrcDir)
    {
        // If there is no composer.lock file, that means that
        // the plugin has autoload classes, but requires no dependencies.
        if (!file_exists("$pluginBaseDir/composer.lock")) {
            return;
        }

        // TODO: Load the composer.lock and analyze it against
        // dirname($terminusSrcDir) . '/composer.lock'.
        throw new TerminusException("Autoloading plugin dependencies is not supported yet.");
    }

    protected function checkAutoloadPath($path)
    {
        $autoloadFile = "$path/vendor/autoload.php";
        if (file_exists($autoloadFile)) {
            return $autoloadFile;
        }
    }
}
