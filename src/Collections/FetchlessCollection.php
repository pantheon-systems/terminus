<?php

namespace Pantheon\Terminus\Collections;

/**
 * Class FetchlessCollection
 * @package Pantheon\Terminus\Collections
 */
abstract class FetchlessCollection extends TerminusCollection
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @inheritdoc
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        if (isset($options['data'])) {
            $this->setData($options['data']);
        }
    }

    /**
     * @return array Returns data array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Processes collection data passed in by the constructor
     *
     * @param array $options Options to configure the function
     * @return array
     */
    protected function getCollectionData($options = [])
    {
        return $this->getData();
    }
}
