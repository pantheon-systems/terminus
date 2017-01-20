<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\Tags;

/**
 * Class OrganizationSiteMembership
 * @package Pantheon\Terminus\Models
 */
class OrganizationSiteMembership extends TerminusModel implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var Organization
     */
    public $organization;
    /**
     * @var Site
     */
    public $site;
    /**
     * @var Tags
     */
    public $tags;
    /**
     * @var \stdClass
     */
    protected $site_data;
    /**
     * @var \stdClass
     */
    protected $tags_data;


    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->organization = $options['collection']->organization;
        $this->site_data = $attributes->site;
        $this->tags_data = $attributes->tags;
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        $org = $this->getOrganization();
        return "{$org->id}: {$org->get('profile')->name}";
    }

    /**
     * Removes a site from this organization
     *
     * @return Workflow
     */
    public function delete()
    {
        return $this->getOrganization()->getWorkflows()->create(
            'remove_organization_site_membership',
            ['params' => ['site_id' => $this->getSite()->get('id'),],]
        );
    }

    /**
     * @return Site
     */
    public function getSite()
    {
        if (!$this->site) {
            $this->site = $this->getContainer()->get(Site::class, [$this->site_data,]);
            $this->site->memberships = [$this,];
            $this->site->tags = $this->getContainer()->get(Tags::class, [['org_site_membership' => $this,],]);
            $this->site->tags->fetch((array)$this->tags_data);
        }
        return $this->site;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
