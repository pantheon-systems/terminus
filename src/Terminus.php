<?php

namespace Pantheon\Terminus;

use Robo\Application;
use Symfony\Component\Console\Input\InputOption;

class Terminus extends Application
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor.
     *
     * @param string $name    The name of the application
     * @param string $version The version of the application
     * @param Config $config  A Terminus configuration object
     */
    public function __construct($name = 'Terminus', $version = null, Config $config = null)
    {
        $this->config = $config;
        parent::__construct($name, $version);
        $this->getDefinition()->addOption(
            new InputOption('--yes', '-y', InputOption::VALUE_NONE, 'Answer yes to all prompts')
        );
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
}
