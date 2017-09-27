<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\DNSRecord;

/**
 * Class DNSRecords
 * @package Pantheon\Terminus\Collections
 */
class DNSRecords extends FetchlessCollection
{
    public static $pretty_name = 'DNS Records';
    /**
     * @var string
     */
    protected $collected_class = DNSRecord::class;
}
