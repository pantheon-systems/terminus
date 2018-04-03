<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\SiteAuthorization;

/**
 * Class SiteAuthorizations
 * @package Pantheon\Terminus\Collections
 */
class SiteAuthorizations extends SiteOwnedCollection
{
    /**
     * @var string
     */
    protected $collected_class = SiteAuthorization::class;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/authorizations';

    /**
     * Determines if the logged-in user may perform the given action on the site.
     *
     * @param string $permission_title The name of the permission to check
     * @return bool
     * @throws TerminusNotFoundException
     */
    public function can($permission_title)
    {
        return $this->get($permission_title)->get('is_user_authorized');
    }
}
