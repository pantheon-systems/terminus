<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Tag
 *
 * @package Pantheon\Terminus\Models
 */
class Tag extends TerminusModel
{
    public const PRETTY_NAME = 'tag';

    /**
     * Removes a tag from the organization/site membership
     */
    public function delete()
    {
        $membership = $this->collection->getMembership();
        $this->request->request(
            sprintf(
                'organizations/%s/tags/%s/sites?entity=%s',
                $membership->attributes->organization_id,
                $this->id,
                $membership->attributes->site_id
            ),
            ['method' => 'delete',]
        );
    }
}
