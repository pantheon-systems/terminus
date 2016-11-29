<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Environment;

/**
 * Class EnvironmentOwnedCollection
 * @package Pantheon\Terminus\Collections
 */
class EnvironmentOwnedCollection extends TerminusCollection
{
    /**
     * @var Environment
     */
    public $environment;

    /**
     * Object constructor
     *
     * @param array $options Options to set as $this->key
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->setEnvironment($options['environment']);
    }

    /**
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @param Environment $environment
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return $this->replaceUrlTokens(parent::getUrl());
    }

    /**
     * @param $url
     * @return string
     */
    protected function replaceUrlTokens($url)
    {
        $tr = [
            '{environment_id}' => $this->getEnvironment()->id,
            '{site_id}' => $this->getEnvironment()->site->id
        ];
        return strtr($url, $tr);
    }
}
