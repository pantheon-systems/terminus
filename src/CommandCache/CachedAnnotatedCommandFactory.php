<?php

namespace Pantheon\Terminus\CommandCache;

use Consolidation\AnnotatedCommand\AnnotatedCommandFactory;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Pantheon\Terminus\DataStore\DataStoreAwareInterface;
use Pantheon\Terminus\DataStore\DataStoreAwareTrait;
use Symfony\Component\Console\Input\InputOption;

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
    public function createCommandsFromClass(
        $commandFileInstance,
        $includeAllPublicMethods = null
    ) {

        $includeAllPublicMethods = $this->getIncludeAllPublicMethods();
      
        $this->notify($commandFileInstance);

        $class = get_class($commandFileInstance);
        $cache_data = $this->getDataStore()->get($class);
        if (!$cache_data) {
            $commandInfoList = $this->getCommandInfoListFromClass($commandFileInstance);
            $cache_data = [];
            foreach ($commandInfoList as $i => $commandInfo) {
                if (static::isCommandMethod($commandInfo, $includeAllPublicMethods)) {
                    $cache_data[$i] = $this->serializeCommandInfo($commandInfo);
                }
            }
            $this->getDataStore()->set($class, $cache_data);
        } else {
            $commandInfoList = [];
            foreach ($cache_data as $i => $data) {
                $commandInfoList[$i] = $this->unserializeCommandInfo((array)$data);
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

  /**
   * Convert a CommandInfo class into a serializable array.
   *
   * @param CommandInfo $commandInfo
   * @return array
   */
    protected function serializeCommandInfo($commandInfo)
    {
        $info = [
        'name' => $commandInfo->getName(),
        'method_name' => $commandInfo->getMethodName(),
        'description' => $commandInfo->getDescription(),
        'help' => $commandInfo->getHelp(),
        'aliases' => $commandInfo->getAliases(),
        'annotations' => $commandInfo->getAnnotations()->getArrayCopy(),
        // Todo: Test This.
        'topics' => $commandInfo->getTopics(),
        'example_usages' => $commandInfo->getExampleUsages(),
        'return_type' => $commandInfo->getReturnType(),
        'parameters' => [],
        'arguments' => [],
        'arguments' => [],
        'options' => [],
        'input_options' => $commandInfo->inputOptions()
        ];
        foreach ($commandInfo->arguments()->getValues() as $key => $val) {
            $info['arguments'][$key] = [
            'description' => $commandInfo->arguments()->getDescription($key),
            ];
            if ($commandInfo->arguments()->hasDefault($key)) {
                $info['arguments'][$key]['default'] = $val;
            }
        }
        foreach ($commandInfo->options()->getValues() as $key => $val) {
            $info['options'][$key] = [
            'description' => $commandInfo->arguments()->getDescription($key),
            ];
            if ($commandInfo->options()->hasDefault($key)) {
                $info['options'][$key]['default'] = $val;
            }
        }
        foreach ($commandInfo->getParameters() as $i => $parameter) {
            // TODO: Support input/output params
        }
        foreach ($commandInfo->inputOptions() as $i => $option) {
            $mode = 0;
            if ($option->isValueRequired()) {
                $mode |= InputOption::VALUE_REQUIRED;
            }
            if ($option->isValueOptional()) {
                $mode |= InputOption::VALUE_OPTIONAL;
            }
            if ($option->isArray()) {
                $mode |= InputOption::VALUE_IS_ARRAY;
            }
            if (!$mode) {
                $mode = InputOption::VALUE_NONE;
            }

            $info['input_options'][$i] = [
            'name' => $option->getName(),
            'shortcut' => $option->getShortcut(),
            'mode' => $mode,
            'description' => $option->getDescription(),
            'default' => null,
            ];
            if ($option->isValueOptional()) {
                $info['input_options'][$i]['default'] = $option->getDefault();
            }
        }
        return $info;
    }

  /**
   * Convert an array into a CommandInfo
   *
   * @param array $info
   * @return CachedCommandInfo
   */
    protected function unserializeCommandInfo($info)
    {
    //    error_log(var_export($info, true));die;
        return new CachedCommandInfo($info);
    }
}
