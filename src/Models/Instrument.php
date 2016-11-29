<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Instrument
 * @package Pantheon\Terminus\Models
 */
class Instrument extends TerminusModel
{
    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return ['id' => $this->id, 'label' => $this->get('label'),];
    }
}
