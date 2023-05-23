<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Organization;
use Pantheon\Terminus\Models\UserOrganizationMembership;

class Organizations extends UserOwnedCollection
{
    /**
     * @var string
     */
    protected $collected_class = Organization::class;

    /**
     * @var string
     */
    protected $url = 'users/{user_id}/memberships/organizations';

    /**
     * @var string
     */
    protected $user_owned_class = UserOrganizationMembership::class;

    /**
     * @var string
     */
    protected $user_owned_url = 'users/{user_id}/memberships/organizations';

    /**
     * @inheritdoc
     */
    public function fetch(array $options = [])
    {
        $options['query']['paged'] = true;
        return parent::fetch($options);
    }
}
