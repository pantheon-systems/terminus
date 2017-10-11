<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Friends\DomainInterface;
use Pantheon\Terminus\Friends\DomainTrait;
use Pantheon\Terminus\Models\DNSRecord;

/**
 * Class DNSRecords
 * @package Pantheon\Terminus\Collections
 */
class DNSRecords extends TerminusCollection implements DomainInterface
{
    use DomainTrait;

    public static $pretty_name = 'DNS Records';
    /**
     * @var string
     */
    protected $collected_class = DNSRecord::class;
}
