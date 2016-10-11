<?php

namespace Terminus\Collections;

use Terminus\Models\OrganizationSiteMembership;
use Terminus\Models\Tag;

class Tags extends TerminusCollection
{
    /**
     * @var OrganizationSiteMembership
     */
    public $org_site_membership;
    /**
     * @var string
     */
    protected $collected_class = 'Terminus\Models\Tag';

    /**
     * @inheritdoc
     */
    public function __construct($options = [])
    {
        $this->org_site_membership = $options['org_site_membership'];
        parent::__construct($options);
    }

    /**
     * Creates a tag for an organization-site relationship
     *
     * @param string $tag Name of tag to create
     * @return Tag $this->models[$tag] The newly created tag
     */
    public function create($tag)
    {
        $params = [$tag => ['sites' => [$this->org_site_membership->site->id,],],];
        $this->request->request(
            "organizations/{$this->org_site_membership->organization->id}/tags",
            ['method' => 'put', 'form_params' => $params,]
        );
        $this->models[$tag] = new Tag((object)['id' => $tag,], ['collection' => $this,]);
        return $this->models[$tag];
    }

    /**
     * @inheritdoc
     */
    public function fetch(array $options = [])
    {
        foreach ($options as $tag_string) {
            if (is_string($tag_string)) {
                $this->add((object)['id' => $tag_string,]);
            }
        }
        return $this;
    }
}
