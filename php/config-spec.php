<?php

return array(
  'interactive' => array(
    'runtime' => '',
    'file' => '<bool>',
    'default' => false,
    'desc' => 'Gather input interactively',
  ),
  'verbose' => array(
    'runtime' => '',
    'file' => '<bool>',
    'default' => false,
    'desc' => 'Show verbose output',
  ),
  'colorize' => array(
    'runtime' => '',
    'file' => '<bool>',
    'default' => true,
    'desc' => 'Use color in output',
  ),
  'debug' => array(
    'runtime' => '',
    'file'  => '<bool>',
    'default' => false,
    'desc'  => 'Get debug output',
  ),
  'yes' => array(
    'runtime' => '',
    'file'  => '<bool>',
    'default' => false,
    'desc'  => 'Answer yes to all prompts',
  ),
  'format' => array(
    'runtime' => '=<json|bash|silent>',
    'file' => '=<json|bash|silent>',
    'default' => 'normal',
    'desc' => 'Force json output',
  ),
);
