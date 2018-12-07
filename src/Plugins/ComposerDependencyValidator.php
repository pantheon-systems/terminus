<?php

namespace Pantheon\Terminus\Plugins;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\Hooks\InitializeHookInterface;
use Pantheon\Terminus\Exceptions\TerminusException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Input\InputInterface;

class ComposerDependencyValidator
{
    protected $src_dir;

    /**
     * Pass in the source directory where Terminus sources are located.
     */
    public function __construct($src_dir)
    {
        $this->src_dir = $src_dir;
    }

    /**
     * Compare the contents of the composer.json and composer.lock files
     * of the plugin being used, and see if anything contaied therein is
     * incompatible with the projects loaded by Terminus itself.
     */
    public function validate($plugin_dir)
    {
        $composer_json_file = $plugin_dir . DIRECTORY_SEPARATOR . 'composer.json';
        $composer_lock_file = $plugin_dir . DIRECTORY_SEPARATOR . 'composer.lock';
        $plugin_composer_json = $this->loadJson($composer_json_file);
        $plugin_composer_lock = $this->loadJson($composer_lock_file);
        $plugin_vendor_dir = dirname($composer_json_file) . "/vendor";

        // If there is no composer.lock file, that means that
        // the plugin has autoload classes, but requires no dependencies.
        // In this case, we know it is safe to load the autoload file.
        if (empty($plugin_composer_lock)) {
            return;
        }

        $composer_json_file = dirname($this->src_dir) . DIRECTORY_SEPARATOR . 'composer.json';
        $composer_lock_file = dirname($this->src_dir) . DIRECTORY_SEPARATOR . 'composer.lock';
        $terminus_composer_json = $this->loadJson($composer_json_file);
        $terminus_composer_lock = $this->loadJson($composer_lock_file);

        if (empty($terminus_composer_json) || empty($terminus_composer_lock)) {
            throw new TerminusException("Could not load Terminus composer data.");
        }

        // If the plugin contains a requirement for something that is part
        // of Terminus' autoload file, reject the plugin. These should be
        // fixed by the plugin author by removing .
        $this->validatePluginDoesNotRequireTerminusDependencies($plugin_composer_json, $terminus_composer_lock);

        // If the plugin's lock file contains a project that is also in
        // Terminus' lock file, require them to be at exactly the same
        // version number.
        $this->validateLockFilesCompatible($plugin_composer_json, $plugin_composer_lock, $terminus_composer_lock, $plugin_vendor_dir);
    }

    /**
     * Ensure that the plugin does not directly 'require' any dependency
     * that Terminus already provides. If it does, we will reject the
     * plugin. The plugin author may remove this dependency from the
     * plugin's composer.json file via 'composer remove'.
     */
    protected function validatePluginDoesNotRequireTerminusDependencies($plugin_composer_json, $terminus_composer_lock)
    {
        $plugin_name = $plugin_composer_json['name'];
        $terminus_packages = $this->getLockFilePackages($terminus_composer_lock);

        // No requirements, no issue. This condition should never be
        // true, though, for if it were, there would be no composer.lock.
        if (!isset($plugin_composer_json['require'])) {
            return;
        }
        $plugin_requirements = $plugin_composer_json['require'];
        unset($plugin_requirements['php']);

        foreach ($plugin_requirements as $project => $version_constraints) {
            if (array_key_exists($project, $terminus_packages)) {
                throw new TerminusException("The plugin {name} requires the project {dependency}, which is already provided by Terminus. Please remove this dependency from the plugin by running 'composer remove {dependency}' in the {name} plugin directory.", ['name' => $plugin_name, 'dependency' => $project]);
            }
        }
    }

    /**
     * Ensure that either:
     *  a) nothing in the plugin composer.lock exists in Terminus's composer.lock (ideal)
     * or
     *  b) anything that does appear in both places exists as exactly the same version.
     */
    protected function validateLockFilesCompatible($plugin_composer_json, $plugin_composer_lock, $terminus_composer_lock, $plugin_vendor_dir)
    {
        $plugin_name = $plugin_composer_json['name'];
        $plugin_packages = $this->getLockFilePackages($plugin_composer_lock);
        $terminus_packages = $this->getLockFilePackages($terminus_composer_lock);

        foreach ($plugin_packages as $project => $version) {
            if (array_key_exists($project, $terminus_packages)) {
                if ($version != $terminus_packages[$project]) {
                    if (is_dir("$plugin_vendor_dir/$project")) {
                        throw new TerminusException("The plugin {name} has installed the project {dependency}: {version}, but Terminus has installed {dependency}: {otherversion}. To resolve this, try running 'composer update' in both the plugin directory, and the terminus directory.", ['name' => $plugin_name, 'dependency' => $project, 'version' => $version, 'otherversion' => $terminus_packages[$project]]);
                    }
                }
            }
        }
    }

    /**
     * Look through the composer.lock file and gather up all of the
     * projects contained therein. Return them as a simple associative
     * array of name => version mappings.
     */
    protected function getLockFilePackages($composer_lock)
    {
        $composer_lock += ['packages' => [], 'packages-dev' => []];

        return $this->collectLockFilePackages($composer_lock['packages']) + $this->collectLockFilePackages($composer_lock['packages-dev']);
    }

    /**
     * Like 'getLockFilePackages', but operates on just one
     * packages section.
     */
    protected function collectLockFilePackages($packages)
    {
        $result = [];

        foreach ($packages as $package) {
            $result[$package['name']] = $package['version'];
        }

        return $result;
    }

    /**
     * Read the contents of a file and convert to a json array.
     */
    protected function loadJson($json_file)
    {
        if (!file_exists($json_file)) {
            return [];
        }
        $contents = file_get_contents($json_file);
        return empty($contents) ? [] : json_decode($contents, true);
    }
}
