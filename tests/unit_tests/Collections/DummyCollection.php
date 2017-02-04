<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\TerminusCollection;

class DummyCollection extends TerminusCollection
{
    /**
     * @inheritdoc
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);
        foreach ($options as $key => $option) {
            $this->$key = $option;
        }
    }
}
