<?php

return array(
  'colorize' => array(
    'runtime' => '',
    'file'    => '<bool>',
    'default' => true,
    'desc'    => 'Use color in output',
  ),
  'debug' => array(
    'runtime' => '',
    'file'    => '<bool>',
    'default' => false,
    'desc'    => 'Get debug output',
  ),
  'yes' => array(
    'runtime' => '',
    'file'    => '<bool>',
    'default' => false,
    'desc'    => 'Answer yes to all prompts',
  ),
  'format' => array(
    'runtime' => '=<json|bash|silent>',
    'file'    => '=<json|bash|silent>',
    'default' => 'normal',
    'desc'    => 'Change the output type to JSON, bash, or silent',
  ),
);
