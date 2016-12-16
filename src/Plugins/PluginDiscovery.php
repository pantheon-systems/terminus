<?php

namespace Pantheon\Terminus\Plugins;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class PluginDiscovery
 */
class PluginDiscovery implements ContainerAwareInterface, LoggerAwareInterface
{
    use ContainerAwareTrait;
    use LoggerAwareTrait;

    /**
     * @var string The path to the directory to search for plugins.
     */
    protected $directory_path;

    /**
     * PluginDiscovery constructor.
     *
     * @param $path
     */
    public function __construct($path)
    {
        $this->directory_path = $path;
    }

    /**
     * Return a list of plugin
     *
     * @return PluginInfo[]
     */
    public function discover()
    {
        $out = [];
        try {
            $di = new \DirectoryIterator($this->directory_path);
            foreach ($di as $dir) {
                if ($dir->isDir() && !$dir->isDot() && $dir->isReadable()) {
                    try {
                        $out[] = $this->getContainer()->get(PluginInfo::class, [$dir->getPathname()]);
                    } catch (TerminusException $e) {
                        $this->logger->warning(
                            'Plugin Discovery: Ignoring directory {dir} because: {msg}.',
                            ['dir' => $dir->getPathName(), 'msg' => $e->getMessage()]
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            // Plugin directory probably didn't exist or wasn't writable. Do nothing.
        }
        return $out;
    }
}
