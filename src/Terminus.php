<?php

namespace Pantheon\Terminus;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;

class Terminus extends Application
{

    /**
     * @inheritdoc
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
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
