<?php

namespace Pantheon\Terminus\Models;

/**
 * Class DNSRecord
 * @package Pantheon\Terminus\Models
 */
class DNSRecord extends TerminusModel
{
    public static $pretty_name = 'DNS record';

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return [
            'domain' => $this->collection->getDomain()->id,
            'status' => $this->get('status'),
            'status_message' => $this->get('status_message'),
            'type' => $this->get('type'),
            'value' => $this->get('target_value'),
            'detected_value' => $this->get('detected_value'),
        ];
    }
}
