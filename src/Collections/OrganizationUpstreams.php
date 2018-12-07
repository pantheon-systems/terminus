<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\OrganizationUpstream;

/**
 * Class OrganizationUpstreams
 * @package Pantheon\Terminus\Collections
 */
class OrganizationUpstreams extends OrganizationOwnedCollection
{
    const PRETTY_NAME = 'upstreams';
    /**
     * @var string
     */
    protected $collected_class = OrganizationUpstream::class;
    /**
     * @var string
     */
    protected $url = 'organizations/{organization_id}/upstreams';

    /**
     * Filters an array of Upstreams by their label
     *
     * @param string $regex Non-delimited PHP regex to filter site names by
     * @return Upstreams
     */
    public function filterByName($regex = '(.*)')
    {
        return $this->filterByRegex('label', $regex);
    }
}
