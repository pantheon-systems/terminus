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
        if ($this->pathIsInside($path, $terminusSrcDir)) {
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

    /**
     * Return 'true' if $path is contained anywhere inside
     * the provided $terminusSrcDir.
     */
    protected function pathIsInside($path, $terminusSrcDir)
    {
        return substr($path, 0, strlen($terminusSrcDir)) == $terminusSrcDir;
    }

    /**
     * Find the plugin's base directory -- the one that contains the
     * composer.json file.
     */
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

    /**
     * Return the path to the autoload file, relative to the
     * provided path, if it exists.
     */
    protected function checkAutoloadPath($path)
    {
        $autoloadFile = "$path/vendor/autoload.php";
        if (file_exists($autoloadFile)) {
            return $autoloadFile;
        }
    }

    /**
     * Compare the contents of the composer.json and composer.lock files
     * of the plugin being used, and see if anything contaied therein is
     * incompatible with the projects loaded by Terminus itself.
     */
    protected function validateComposerLock($pluginBaseDir, $terminusSrcDir)
    {
        $pluginComposerJsonFile = $pluginBaseDir . '/composer.json';
        $pluginComposerLockFile = $pluginBaseDir . '/composer.lock';
        $pluginComposerJson = $this->loadJson($pluginComposerJsonFile);
        $pluginComposerLock = $this->loadJson($pluginComposerLockFile);

        // If there is no composer.lock file, that means that
        // the plugin has autoload classes, but requires no dependencies.
        // In this case, we know it is safe to load the autoload file.
        if (empty($pluginComposerLock)) {
            return;
        }

        $terminusComposerJsonFile = dirname($terminusSrcDir) . '/composer.json';
        $terminusComposerLockFile = dirname($terminusSrcDir) . '/composer.lock';
        $terminusComposerJson = $this->loadJson($terminusComposerJsonFile);
        $terminusComposerLock = $this->loadJson($terminusComposerLockFile);

        if (empty($terminusComposerJson) || empty($terminusComposerLock)) {
            throw new TerminusException("Could not load Terminus composer data.");
        }

        // If the plugin contains a requirement for something that is part
        // of Terminus' autoload file, reject the plugin. These should be
        // fixed by the plugin author by removing .
        $this->validatePluginDoesNotRequireTerminusDependencies($pluginComposerJsonFile, $terminusComposerLockFile);

        // If the plugin's lock file contains a project that is also in
        // Terminus' lock file, require them to be at exactly the same
        // version number.
        $this->validateLockFilesCompatible($pluginComposerJsonFile, $pluginComposerLockFile, $terminusComposerLockFile);
    }

    /**
     * Ensure that the plugin does not directly 'require' any dependency
     * that Terminus already provides. If it does, we will reject the
     * plugin. The plugin author may remove this dependency from the
     * plugin's composer.json file via 'composer remove'.
     */
    protected function validatePluginDoesNotRequireTerminusDependencies($pluginComposerJsonFile, $terminusComposerLockFile)
    {
        $pluginName = $pluginComposerJsonFile['name'];
        $terminusPackages = $this->getLockFilePackages($terminusComposerLockFile);

        // No requirements, no issue. This condition should never be
        // true, though, for if it were, there would be no composer.lock.
        if (!isset($pluginComposerJsonFile['require'])) {
            return;
        }
        $pluginRequirements = $pluginComposerJsonFile['require'];
        unset($pluginRequirements['php']);

        foreach ($pluginRequirements as $project => $versionConstraints) {
            if (array_key_exists($project, $terminusPackages)) {
                throw new TerminusException("The plugin {name} requires the project {dependency}, which is already provided by Terminus. Please remove this dependency from the plugin by running 'composer remove {dependency}' in the {name} plugin directory.", ['name' => $pluginName, 'dependency' => $project]);
            }
        }
    }

    /**
     * Ensure that either:
     *  a) nothing in the plugin composer.lock exists in Terminus's composer.lock (ideal)
     * or
     *  b) anything that does appear in both places exists as exactly the same version.
     */
    protected function validateLockFilesCompatible($pluginComposerJsonFile, $pluginComposerLockFile, $terminusComposerLockFile)
    {
        $pluginName = $pluginComposerJsonFile['name'];
        $pluginPackages = $this->getLockFilePackages($pluginComposerLockFile);
        $terminusPackages = $this->getLockFilePackages($terminusComposerLockFile);

        foreach ($pluginPackages as $project => $version) {
            if (array_key_exists($project, $terminusPackages)) {
                if ($version != $terminusPackages[$project]) {
                    throw new TerminusException("The plugin {name} has installed the project {dependency}: {version}, but Terminus has installed {dependency}: {otherversion}. To resolve this, try running 'composer update' in both the plugin directory, and the terminus directory.", ['name' => $pluginName, 'dependency' => $project, 'version' => $version, 'otherversion' => $terminusPackages[$project]]);
                }
            }
        }
    }

    /**
     * Look through the composer.lock file and gather up all of the
     * projects contained therein. Return them as a simple associative
     * array of name => version mappings.
     */
    protected function getLockFilePackages($lockFile)
    {
        $lockFile += ['packages' => [], 'packages-dev' => []];

        return collectLockFilePackages($lockFile['packages']) + collectLockFilePackages($lockFile['packages-dev']);
    }

    /**
     * Like 'getLockFilePackages', but operates on just one
     * packages section.
     */
    protected function collectLockFilePackages($packages)
    {
        $result;

        foreach ($packages as $package) {
            $result[$packages['name']] = $packages['version'];
        }

        return $result;
    }

    /**
     * Read the contents of a file and convert to a json array.
     */
    protected function loadJson($pathToJson)
    {
        if (!file_exists($pathToJson)) {
            return [];
        }
        $contents = file_get_contents($pathToJson);
        if (empty($contents)) {
            return [];
        }
        return json_decode($contents, true);
    }
}
