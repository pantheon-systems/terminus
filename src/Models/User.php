<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\Instruments;
use Pantheon\Terminus\Collections\MachineTokens;
use Pantheon\Terminus\Collections\SshKeys;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Collections\UserSiteMemberships;
use Pantheon\Terminus\Collections\Workflows;

class User extends TerminusModel implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var \stdClass
     * @todo Wrap this in a proper class.
     */
    protected $aliases;
    /**
     * @var Instruments
     */
    protected $instruments;
    /**
     * @var Instruments
     */
    protected $machine_tokens;
    /**
     * @var UserOrganizationMemberships
     */
    protected $org_memberships;
    /**
     * @var UserSiteMemberships
     */
    protected $site_memberships;
    /**
     * @var SshKeys
     */
    protected $ssh_keys;
    /**
     * @var Workflows
     */
    protected $workflows;

    /**
     * Object constructor
     *
     * @param object $attributes Attributes of this model
     * @param array $options Options with which to configure this model
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->url = "users/{$this->id}";
    }

    /**
     * Retrieves organization data for this user
     *
     * @return Organization[]
     */
    public function getOrganizations()
    {
        $org_memberships = $this->getOrgMemberships()->all();
        $organizations = array_combine(
            array_map(
                function ($membership) {
                    return $membership->getOrganization()->id;
                },
                $org_memberships
            ),
            array_map(
                function ($membership) {
                    return $membership->getOrganization();
                },
                $org_memberships
            )
        );
        return $organizations;
    }

    /**
     * Requests API data and returns an object of user site data
     *
     * @return Site[]
     */
    public function getSites()
    {
        $site_memberships = $this->getSiteMemberships()->all();
        $sites = array_combine(
            array_map(
                function ($membership) {
                    return $membership->site->id;
                },
                $site_memberships
            ),
            array_map(
                function ($membership) {
                    return $membership->site;
                },
                $site_memberships
            )
        );
        return $sites;
    }

    /**
     * Formats User object into an associative array for output
     *
     * @return array $data associative array of data for output
     */
    public function serialize()
    {
        $first_name = $last_name = null;
        if (isset($this->get('profile')->firstname)) {
            $first_name = $this->get('profile')->firstname;
        }
        if (isset($this->get('profile')->lastname)) {
            $last_name = $this->get('profile')->lastname;
        }

        $data = [
            'firstname' => $first_name,
            'lastname' => $last_name,
            'email' => $this->get('email'),
            'id' => $this->id,
        ];
        return $data;
    }

    /**
     * Retrieves Drush aliases for this user
     *
     * @return \stdClass
     */
    public function getAliases()
    {
        if (!$this->aliases) {
            $this->fetchAliases();
        }
        return $this->aliases;
    }

    /**
     * Requests API data and populates $this->aliases
     *
     * @return void
     */
    private function fetchAliases()
    {
        $path = "users/{$this->id}/drush_aliases";
        $options = ['method' => 'get',];
        $response = $this->request->request($path, $options);

        $this->aliases = $response['data']->drush_aliases;
    }

    /**
     * @return \Terminus\Collections\Instruments
     */
    public function getInstruments()
    {
        if (empty($this->instruments)) {
            $this->instruments = $this->getContainer()->get(Instruments::class, [['user' => $this,]]);
        }
        return $this->instruments;
    }

    /**
     * @return \Terminus\Collections\Instruments
     */
    public function getMachineTokens()
    {
        if (empty($this->machine_tokens)) {
            $this->machine_tokens = $this->getContainer()->get(MachineTokens::class, [['user' => $this,]]);
        }
        return $this->machine_tokens;
    }

    /**
     * @return \Terminus\Collections\UserOrganizationMemberships
     */
    public function getOrgMemberships()
    {
        if (empty($this->org_memberships)) {
            $this->org_memberships = $this->getContainer()
                ->get(UserOrganizationMemberships::class, [['user' => $this,]]);
        }
        return $this->org_memberships;
    }

    /**
     * @return \Terminus\Collections\UserSiteMemberships
     */
    public function getSiteMemberships()
    {
        if (empty($this->site_memberships)) {
            $this->site_memberships = $this->getContainer()->get(UserSiteMemberships::class, [['user' => $this,]]);
        }
        return $this->site_memberships;
    }

    /**
     * @return \Terminus\Collections\SshKeys
     */
    public function getSshKeys()
    {
        if (empty($this->ssh_keys)) {
            $this->ssh_keys = $this->getContainer()->get(SshKeys::class, [['user' => $this,]]);
        }
        return $this->ssh_keys;
    }

    /**
     * @return \Terminus\Collections\Workflows
     */
    public function getWorkflows()
    {
        if (empty($this->workflows)) {
            $this->workflows = $this->getContainer()->get(Workflows::class, [['user' => $this,]]);
        }
        return $this->workflows;
    }
}
