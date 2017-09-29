<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\Domain;

/**
 * Interface DomainInterface
 * @package Pantheon\Terminus\Friends
 */
interface DomainInterface
{
    /**
     * @return Domain Returns an Domain-type object
     */
    public function getDomain();

    /**
     * @param Domain $domain
     */
    public function setDomain(Domain $domain);
}
