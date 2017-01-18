<?php

namespace Pantheon\Terminus\CommandCache;

use Consolidation\AnnotatedCommand\AnnotationData;
use Consolidation\AnnotatedCommand\Parser\CommandInfo;
use Consolidation\AnnotatedCommand\Parser\DefaultsWithDescriptions;
use Symfony\Component\Console\Input\InputOption;

class CachedCommandInfo extends CommandInfo
{

    /**
     * @var array
     */
    protected $input_options;

    public function __construct($info_array)
    {
        // @TODO hydrate topics
        // @TODO hydrate parameters
        $this->name = $info_array['name'];
        $this->methodName = $info_array['method_name'];
        $this->otherAnnotations = new AnnotationData((array) $info_array['annotations']);
        $this->arguments = static::unserializeDefaultsWithDescriptions((array)$info_array['arguments']);
        $this->options = static::unserializeDefaultsWithDescriptions((array)$info_array['options']);
        $this->aliases = $info_array['aliases'];
        $this->help = $info_array['help'];
        $this->description = $info_array['description'];
        $this->exampleUsage = $info_array['example_usages'];
        $this->returnType = $info_array['return_type'];
        $this->input_options = static::unserializeInputOptions((array)$info_array['input_options']);
    }

    /**
     * Hydrate a DefaultsWithDescriptions object from an array
     * @param array $array
     * @return \Consolidation\AnnotatedCommand\Parser\DefaultsWithDescriptions
     */
    protected static function unserializeDefaultsWithDescriptions(array $array)
    {
        $out = new DefaultsWithDescriptions();
        foreach ($array as $key => $info) {
            $info = (array)$info;
            $out->add($key, $info['description']);
            if (array_key_exists('default', $info)) {
                $out->setDefaultValue($key, $info['default']);
            }
        }
        return $out;
    }

    /**
     * Hydrate an array of InputOptions from an array
     * @param array $array
     * @return InputOption[]
     */
    protected static function unserializeInputOptions(array $array)
    {
        $out = [];
        foreach ($array as $i => $option) {
            $option = (array) $option;
            $out[$i] = new InputOption(
                $option['name'],
                $option['shortcut'],
                $option['mode'],
                $option['description'],
                $option['default']
            );
        }
        return $out;
    }

    /**
     * Convert a CommandInfo class into a serializable array.
     *
     * @param CommandInfo $commandInfo
     * @return array
     */
    public static function serialize(CommandInfo $commandInfo)
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
          'parameters' => static::serializeParameters($commandInfo->getParameters()),
          'arguments' => static::serializeDefaultsWithDescriptions($commandInfo->arguments()),
          'options' => static::serializeDefaultsWithDescriptions($commandInfo->options()),
          'input_options' => static::serializeInputOptions($commandInfo->inputOptions()),
        ];
        return $info;
    }


    /**
     * Convert an array into a CommandInfo object.
     *
     * @param array $info_array
     * @return \Pantheon\Terminus\CommandCache\CachedCommandInfo
     */
    public static function unserialize(array $info_array)
    {
        return new CachedCommandInfo($info_array);
    }

    /**
     * Serialize a DefaultsWithDescriptions object into an array
     *
     * @param \Consolidation\AnnotatedCommand\Parser\DefaultsWithDescriptions $in
     * @return array
     */
    protected static function serializeDefaultsWithDescriptions(DefaultsWithDescriptions $in)
    {
        $out = [];
        foreach ($in->getValues() as $key => $val) {
            $info['arguments'][$key] = [
              'description' => $in->getDescription($key),
            ];
            if ($in->hasDefault($key)) {
                $info['arguments'][$key]['default'] = $val;
            }
        }
        return $out;
    }

    /**
     * Convert a list of InputOption objects into an array.
     * @param array $in
     * @return array
     */
    protected static function serializeInputOptions(array $in)
    {
        $out = [];
        foreach ($in as $i => $option) {
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
        return $out;
    }

    /**
     * Convert a list of parameters into a serializable array.
     *
     * @param array $in
     * @return array
     */
    protected static function serializeParameters($in)
    {
        $out = [];
        // TODO: Support input/output params
        return $out;
    }


    /**
     * Get the inputOptions for the options associated with this CommandInfo
     * object, e.g. via @option annotations, or from
     * $options = ['someoption' => 'defaultvalue'] in the command method
     * parameter list.
     *
     * @return InputOption[]
     */
    public function inputOptions()
    {
        return $this->input_options;
    }


    /**
     * Return the list of refleaction parameters.
     *
     * @return ReflectionParameter[]
     */
    public function getParameters()
    {
        // TODO: Fix this.
        return [];
    }

    /**
     * Parse the docBlock comment for this command, and set the
     * fields of this class with the data thereby obtained.
     */
    protected function parseDocBlock()
    {
        // Do nothing.
    }
}
