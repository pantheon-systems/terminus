<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Tag
 * @package Pantheon\Terminus\Models
 */
class Tag extends TerminusModel
{
    public static $pretty_name = 'tag';

    /**
     * Removes a tag from the organization/site membership
     */
    public function delete()
    {
        $membership = $this->collection->getMembership();
        $this->request->request(
            sprintf(
                'organizations/%s/tags/%s/sites?entity=%s',
                $membership->getOrganization()->id,
                $this->id,
                $membership->getSite()->id
            ),
            ['method' => 'delete',]
        );
    }
}
