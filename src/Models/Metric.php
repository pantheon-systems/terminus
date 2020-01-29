<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Metric
 * @package Pantheon\Terminus\Models
 */
class Metric extends TerminusModel
{
    const PRETTY_NAME = 'Metric';

    public function serialize()
    {
        return [
            'datetime' => $this->get('datetime'),
            'pages_served' => $this->get('pages_served'),
            'visits' => $this->get('visits'),
        ];
    }
}
