<?php
// src/Terminus.php

use Symfony\Component\Console\Application;
use Terminus\Config;

class Terminus extends Application
{

  /**
   * @inheritdoc
   */
    public function __construct()
    {
        $this->setAutoloader();
        parent::__construct('Terminus', Config::get('version'));
        date_default_timezone_set(Config::get('time_zone'));
    }

  /**
   * @inheritdoc
   */
    protected function getDefaultHelperSet()
    {
        $helper_set = parent::getDefaultHelperSet();
        //$helper_set->set(new Terminus\Helpers\FileHelper());
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

  /**
   * Sets the autoloader up to retrieve files as necessary
   *
   * @return void
   */
    private function setAutoloader()
    {
        $loader = new Psr4ClassLoader();
        $loader->addPrefix('Terminus\\', __DIR__);
        $loader->addPrefix('Terminus\\Models', __DIR__ . '../php/Terminus/Models');
        $loader->addPrefix('Terminus\\Collections', __DIR__ . '../php/Terminus/Collections');
        $loader->register();
    }
}
