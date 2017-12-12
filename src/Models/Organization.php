<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\OrganizationSiteMemberships;
use Pantheon\Terminus\Collections\OrganizationUpstreams;
use Pantheon\Terminus\Collections\OrganizationUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Friends\ProfileInterface;
use Pantheon\Terminus\Friends\ProfileTrait;
use Pantheon\Terminus\Friends\SitesInterface;
use Pantheon\Terminus\Friends\SitesTrait;
use Pantheon\Terminus\Friends\UsersInterface;
use Pantheon\Terminus\Friends\UsersTrait;

/**
 * Class Organization
 * @package Pantheon\Terminus\Models
 */
class Organization extends TerminusModel implements
    ContainerAwareInterface,
    ProfileInterface,
    SitesInterface,
    UsersInterface
{
    use ContainerAwareTrait;
    use ProfileTrait;
    use SitesTrait;
    use UsersTrait;

    public static $pretty_name = 'organization';
    /**
     * @var array
     */
    private $features;
    /**
     * @var OrganizationSiteMemberships
     */
    private $site_memberships;
    /**
     * @var Upstreams
     */
    private $upstreams;
    /**
     * @var OrganizationUserMemberships
     */
    private $user_memberships;
    /**
     * @var Workflows
     */
    private $workflows;

    /**
     * @return string
     */
    public function __toString()
    {
        return "{$this->id}: {$this->getLabel()}";
    }

    /**
     * Returns a specific organization feature value
     *
     * @param string $feature Feature to check
     * @return mixed|null Feature value, or null if not found
     */
    public function getFeature($feature)
    {
        if (!isset($this->features)) {
            $response = $this->request->request("organizations/{$this->id}/features");
            $this->features = (array)$response['data'];
        }
        if (isset($this->features[$feature])) {
            return $this->features[$feature];
        }
        return null;
    }

    /**
     * Get the human-readable name of the organization.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->getProfile()->get('name');
    }

    /**
     * Get the slugified name of the organization.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getProfile()->get('machine_name');
    }

    /**
     * @return string[]
     */
    public function getReferences()
    {
        return $this->serialize();
    }

    /**
     * @return OrganizationSiteMemberships
     */
    public function getSiteMemberships()
    {
        if (empty($this->site_memberships)) {
            $this->site_memberships = $this->getContainer()
                ->get(OrganizationSiteMemberships::class, [['organization' => $this,],]);
        }
        return $this->site_memberships;
    }

    /**
     * @return OrganizationUpstreams
     */
    public function getUpstreams()
    {
        if (empty($this->upstreams)) {
            $this->upstreams = $this->getContainer()->get(OrganizationUpstreams::class, [['organization' => $this,],]);
        }
        return $this->upstreams;
    }

    /**
     * @return OrganizationUserMemberships
     */
    public function getUserMemberships()
    {
        if (empty($this->user_memberships)) {
            $this->user_memberships = $this->getContainer()
                ->get(OrganizationUserMemberships::class, [['organization' => $this,],]);
        }
        return $this->user_memberships;
    }

    /**
     * @return Workflows
     */
    public function getWorkflows()
    {
        if (empty($this->workflows)) {
            $this->workflows = $this->getContainer()->get(Workflows::class, [['organization' => $this,],]);
        }
        return $this->workflows;
    }

    /**
     * Formats the Organization object into an associative array for output
     *
     * @return array Associative array of data for output
     *         string id    The UUID of the organization
     *         string name  The name of the organization
     *         string label The human-readable name of the organization
     */
    public function serialize()
    {
        return ['id' => $this->id, 'name' => $this->getName(), 'label' => $this->getLabel(),];
    }
}
