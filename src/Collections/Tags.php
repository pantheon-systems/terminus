<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\OrganizationSiteMembership;
use Pantheon\Terminus\Models\Tag;

/**
 * Class Tags
 * @package Pantheon\Terminus\Collections
 */
class Tags extends APICollection
{
    const PRETTY_NAME = 'tags';
    /**
     * @var string
     */
    protected $collected_class = Tag::class;
    /**
     * @var OrganizationSiteMembership
     */
    protected $org_site_membership;

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
        $params = [$tag => ['sites' => [$this->org_site_membership->getSite()->id,],],];
        $this->request->request(
            "organizations/{$this->org_site_membership->getOrganization()->id}/tags",
            ['method' => 'put', 'form_params' => $params,]
        );
        $this->models[$tag] = $this->getContainer()->get($this->collected_class, [(object)['id' => $tag,], ['collection' => $this,]]);
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

    /**
     * @return OrganizationSiteMembership
     */
    public function getMembership()
    {
        return $this->org_site_membership;
    }
}
