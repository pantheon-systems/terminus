<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\PaymentMethods;
use Pantheon\Terminus\Collections\MachineTokens;
use Pantheon\Terminus\Collections\SSHKeys;
use Pantheon\Terminus\Collections\Upstreams;
use Pantheon\Terminus\Collections\UserOrganizationMemberships;
use Pantheon\Terminus\Collections\UserSiteMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class User
 * @package Pantheon\Terminus\Models
 */
class User extends TerminusModel implements ConfigAwareInterface, ContainerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;

    /**
     * @var \stdClass
     * @todo Wrap this in a proper class.
     */
    protected $aliases;
    /**
     * @var PaymentMethods
     */
    protected $payment_methods;
    /**
     * @var PaymentMethods
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
     * @var SSHKeys
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
     * Provides Pantheon Dashboard URL for this user
     *
     * @return string
     */
    public function dashboardUrl()
    {
        $url = sprintf(
            '%s://%s/users/%s#sites',
            $this->getConfig()->get('dashboard_protocol'),
            $this->getConfig()->get('dashboard_host'),
            $this->id
        );

        return $url;
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
                    return $membership->getSite()->get('id');
                },
                $site_memberships
            ),
            array_map(
                function ($membership) {
                    return $membership->getSite();
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
     * @return string
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
     */
    private function fetchAliases()
    {
        $path = "users/{$this->id}/drush_aliases";
        $options = ['method' => 'get',];
        $response = $this->request->request($path, $options);

        $this->aliases = $response['data']->drush_aliases;
    }

    /**
     * @return Pantheon\Terminus\Collections\PaymentMethods
     */
    public function getPaymentMethods()
    {
        if (empty($this->payment_methods)) {
            $this->payment_methods = $this->getContainer()->get(PaymentMethods::class, [['user' => $this,]]);
        }
        return $this->payment_methods;
    }

    /**
     * @return \Terminus\Collections\PaymentMethods
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
     * @return object
     */
    public function getProfile()
    {
        return $this->get('profile');
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getProfile()->full_name;
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
     * @return \Terminus\Collections\SSHKeys
     */
    public function getSSHKeys()
    {
        if (empty($this->ssh_keys)) {
            $this->ssh_keys = $this->getContainer()->get(SSHKeys::class, [['user' => $this,]]);
        }
        return $this->ssh_keys;
    }

    /**
     * @return Pantheon\Terminus\Collections\Workflows
     */
    public function getUpstreams()
    {
        if (empty($this->upstreams)) {
            $this->upstreams = $this->getContainer()->get(Upstreams::class, [['user' => $this,]]);
        }
        return $this->upstreams;
    }

    /**
     * @return \Pantheon\Terminus\Collections\Workflows
     */
    public function getWorkflows()
    {
        if (empty($this->workflows)) {
            $this->workflows = $this->getContainer()->get(Workflows::class, [['user' => $this,]]);
        }
        return $this->workflows;
    }
}
