<?php

namespace Pantheon\Terminus\CommandCache;

use Consolidation\AnnotatedCommand\AnnotatedCommandFactory;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Pantheon\Terminus\DataStore\DataStoreAwareInterface;
use Pantheon\Terminus\DataStore\DataStoreAwareTrait;

class CachedAnnotatedCommandFactory extends AnnotatedCommandFactory implements DataStoreAwareInterface
{
    use DataStoreAwareTrait;

    /**
     * Register all commands and hooks. Read info from cache if possible.
     *
     * @param $commandFileInstance
     * @param null $includeAllPublicMethods
     * @return array
     */
    public function createCommandsFromClass($commandFileInstance, $includeAllPublicMethods = null)
    {

        $includeAllPublicMethods = $this->getIncludeAllPublicMethods();

        $this->notify($commandFileInstance);

        $class = get_class($commandFileInstance);
        $cache_data = $this->getDataStore()->get($class);
        if (!$cache_data) {
            $commandInfoList = $this->getCommandInfoListFromClass($commandFileInstance);
            $cache_data = [];
            foreach ($commandInfoList as $i => $commandInfo) {
                if (static::isCommandMethod($commandInfo, $includeAllPublicMethods)) {
                    $cache_data[$i] = $commandInfo->serialize();
                }
            }
            $this->getDataStore()->set($class, $cache_data);
        } else {
            $commandInfoList = [];
            foreach ($cache_data as $i => $data) {
                $commandInfoList[$i] = CommandInfo::deserialize((array)$data);
            }
        }

        $this->registerCommandHooksFromClassInfo(
            $commandInfoList,
            $commandFileInstance
        );
        return $this->createCommandsFromClassInfo(
            $commandInfoList,
            $commandFileInstance,
            $includeAllPublicMethods
        );
    }

    /**
     * Clear the command cache.
     */
    public function clearCache()
    {
        $this->getDataStore()->removeAll();
    }
}
