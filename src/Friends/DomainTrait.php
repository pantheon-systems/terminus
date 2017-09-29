<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Domain;

/**
 * Class DomainTrait
 * @package Pantheon\Terminus\Friends
 */
trait DomainTrait
{
    /**
     * @var Domain
     */
    private $domain;

    /**
     * @inheritdoc
     */
    public function __construct(array $options = [])
    {
        if (isset($options['domain'])) {
            $this->setDomain($options['domain']);
        }
        parent::__construct($options);
    }

    /**
     * @return Domain Returns a Domain-type object
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param Domain $domain
     */
    public function setDomain(Domain $domain)
    {
        $this->domain = $domain;
    }
}
