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
            'value' => $this->get('value'),
        ];
    }
}
