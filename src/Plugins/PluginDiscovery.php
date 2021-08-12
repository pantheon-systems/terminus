<?php

namespace Pantheon\Terminus\Plugins;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Pantheon\Terminus\Plugins\PluginInfo;
use Pantheon\Terminus\Helpers\LocalMachineHelper;

/**
 * Class PluginDiscovery
 */
class PluginDiscovery implements ContainerAwareInterface, LoggerAwareInterface
{
    use ContainerAwareTrait;
    use LoggerAwareTrait;

    /**
     * List of all Terminus plugins that have been rolled into Terminus core.
     */
    const BLACKLIST = [
        'pantheon-systems/terminus-aliases-plugin'
    ];

    /**
     * Return a list of plugin
     *
     * @return PluginInfo[]
     */
    public function discover()
    {
        $config = $this->getContainer()->get('config');
        $dependencies_dir = $config->get('terminus_dependencies_dir');
        var_dump($dependencies_dir);
        $dependencies_composer_lock = [];
        $out = [];
        $composer_lock = [];
        try {
            $local_machine = $this->getContainer()->get(LocalMachineHelper::class);
            if ($local_machine->getFilesystem()->exists($dependencies_dir . '/composer.lock')) {
                $dependencies_composer_lock = \json_decode(
                    file_get_contents($dependencies_dir . '/composer.lock'),
                    true,
                    10,
                    JSON_THROW_ON_ERROR
                );
            }
            if (empty($dependencies_composer_lock['packages'])) {
                // Something is empty, nothing to do.
                return $out;
            }
        } catch (\Exception $e) {
            // Plugin directory probably didn't exist or wasn't writable. Do nothing.
            return $out;
        }

        foreach ($dependencies_composer_lock['packages'] as $package) {
            try {
                if (empty($package['type']) || $package['type'] !== 'terminus-plugin') {
                    continue;
                }
                $plugin = $this->getContainer()->get(PluginInfo::class);
                $plugin->setInfoArray($package);
                if (!in_array($plugin->getName(), self::BLACKLIST)) {
                    $out[] = $plugin;
                }
            } catch (TerminusException $e) {
                $this->logger->warning(
                    'Plugin Discovery: Ignoring directory {dir} because: {msg}.',
                    ['dir' => $dir->getPathName(), 'msg' => $e->getMessage()]
                );
            }
        }
        return $out;
    }
}
