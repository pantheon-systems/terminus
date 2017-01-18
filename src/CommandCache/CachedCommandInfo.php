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
        $this->name = $info_array['name'];
        $this->methodName = $info_array['method_name'];
        $this->otherAnnotations = new AnnotationData((array) $info_array['annotations']);
        $this->arguments = new DefaultsWithDescriptions();
        $this->options = new DefaultsWithDescriptions();
        $this->aliases = $info_array['aliases'];
        $this->help = $info_array['help'];
        $this->description = $info_array['description'];
        $this->exampleUsage = $info_array['example_usages'];
        $this->returnType = $info_array['return_type'];

        foreach ((array)$info_array['arguments'] as $key => $info) {
            $info = (array)$info;
            $this->arguments->add($key, $info['description']);
            if (array_key_exists('default', $info)) {
                $this->arguments->setDefaultValue($key, $info['default']);
            }
        }
        foreach ((array)$info_array['options'] as $key => $info) {
            $info = (array)$info;
            $this->options->add($key, $info['description']);
            if (array_key_exists('default', $info)) {
                $this->options->setDefaultValue($key, $info['default']);
            }
        }

        $this->input_options = [];
        foreach ((array)$info_array['input_options'] as $i => $option) {
            $option = (array) $option;
            $this->input_options[$i] = new InputOption(
                $option['name'],
                $option['shortcut'],
                $option['mode'],
                $option['description'],
                $option['default']
            );
        }


        // Remember the name of the last parameter, if it holds the options.
        // We will use this information to ignore @param annotations for the options.
    //    if (!empty($this->options)) {
    //      $this->optionParamName = $this->lastParameterName();
    //    }
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

    protected function lastParameterName()
    {
        $params = $this->reflection->getParameters();
        $param = end($params);
        if (!$param) {
            return '';
        }
        return $param->name;
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
