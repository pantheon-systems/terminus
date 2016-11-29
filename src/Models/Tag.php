<?php

namespace Pantheon\Terminus\Models;

/**
 * Class Tag
 * @package Pantheon\Terminus\Models
 */
class Tag extends TerminusModel
{
    /**
     * @var OrganizationSiteMembership
     */
    public $org_site_membership;

    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->org_site_membership = $options['collection']->org_site_membership;
    }

    /**
     * Removes a tag from the organization/site membership
     */
    public function delete()
    {
        $this->request->request(
            sprintf(
                'organizations/%s/tags/%s/sites?entity=%s',
                $this->org_site_membership->organization->id,
                $this->id,
                $this->org_site_membership->getSite()->id
            ),
            ['method' => 'delete',]
        );
    }
}
