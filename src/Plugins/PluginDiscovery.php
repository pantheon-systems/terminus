<?php

namespace Pantheon\Terminus\Plugins;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class PluginDiscovery.
 */
class PluginDiscovery implements
    ContainerAwareInterface,
    LoggerAwareInterface,
    ConfigAwareInterface
{
    use ContainerAwareTrait;
    use LoggerAwareTrait;
    use ConfigAwareTrait;

    /**
     * List of all Terminus plugins that have been rolled into Terminus core.
     */
    public const BLACKLIST = [
        'pantheon-systems/terminus-aliases-plugin',
    ];

    /**
     * Returns a list of plugins.
     *
     * @return PluginInfo[]
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function discover()
    {
        $config = $this->getConfig();
        $dependencies_dir = $config->get('terminus_dependencies_dir');
        $dependencies_composer_lock = [];
        $out = [];
        try {
            $local_machine = $this->getContainer()->get(
                LocalMachineHelper::class
            );
            if (
                $local_machine->getFilesystem()->exists(
                    $dependencies_dir . '/composer.lock'
                )
            ) {
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
            if (empty($package['type']) || $package['type'] !== 'terminus-plugin') {
                continue;
            }
            $plugin = $this->getContainer()->get(PluginInfo::class);
            $plugin->setInfoArray($package);
            if (!in_array($plugin->getName(), self::BLACKLIST)) {
                $out[] = $plugin;
            }
        }
        return $out;
    }
}
