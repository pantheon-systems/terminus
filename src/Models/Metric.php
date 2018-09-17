<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Metric
 * @package Pantheon\Terminus\Models
 */
class Metric extends TerminusModel
{
    public static $pretty_name = 'Metric';

    public function serialize()
    {
        return [
            'datetime' => $this->get('datetime'),
            'pages_served' => $this->get('pages_served'),
            'visits' => $this->get('visits'),
            'cache_hits' => $this->get('cache_hits'),
            'cache_misses' => $this->get('cache_misses'),
        ];
    }
}
