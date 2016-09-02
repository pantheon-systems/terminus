<?php
// src/Terminus.php

namespace Pantheon;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Terminus\Config;

class Terminus extends Application
{
  /**
   * @var Config
   */
    private $config;

  /**
   * @inheritdoc
   */
    public function __construct()
    {
        $this->config = new Config();
        parent::__construct('Terminus', $this->config->get('version'));
    }

  /**
   * @inheritdoc
   */
    protected function getDefaultHelperSet()
    {
        $helper_set = parent::getDefaultHelperSet();
        //$helper_set->set(new Pantheon\Terminus\Helpers\FileHelper());
        return $helper_set;
    }

  /**
   * @inheritdoc
   */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();
        $definition->addOptions(
            [
            new InputOption(
                '--yes',
                '-y',
                InputOption::VALUE_NONE,
                'Answer yes to all prompts'
            ),
            ]
        );
        return $definition;
    }
}
