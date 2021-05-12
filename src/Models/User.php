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
use Pantheon\Terminus\Friends\OrganizationsInterface;
use Pantheon\Terminus\Friends\OrganizationsTrait;
use Pantheon\Terminus\Friends\ProfileInterface;
use Pantheon\Terminus\Friends\ProfileTrait;
use Pantheon\Terminus\Friends\SitesInterface;
use Pantheon\Terminus\Friends\SitesTrait;

/**
 * Class User
 * @package Pantheon\Terminus\Models
 */
class User extends TerminusModel implements
    ContainerAwareInterface,
    OrganizationsInterface,
    ProfileInterface,
    SitesInterface
{
    use ContainerAwareTrait;
    use OrganizationsTrait;
    use ProfileTrait;
    use SitesTrait;

    const PRETTY_NAME = 'user';
    /**
     * @var string
     */
    protected $url = 'users/{id}';
    /**
     * @var \stdClass
     * @todo Wrap this in a proper class.
     */
    private $aliases;
    /**
     * @var PaymentMethods
     */
    private $payment_methods;
    /**
     * @var PaymentMethods
     */
    private $machine_tokens;
    /**
     * @var UserOrganizationMemberships
     */
    private $org_memberships;
    /**
     * @var UserSiteMemberships
     */
    private $site_memberships;
    /**
     * @var SSHKeys
     */
    private $ssh_keys;
    /**
     * @var Workflows
     */
    private $workflows;

    /**
     * Provides Pantheon Dashboard URL for this user
     *
     * @return string
     */
    public function dashboardUrl()
    {
        $config = $this->getConfig();
        return "{$config->get('dashboard_protocol')}://{$config->get('dashboard_host')}/users/{$this->id}#sites";
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
        $path = "{$this->getUrl()}/drush_aliases";
        $options = ['method' => 'get',];
        $response = $this->request->request($path, $options);

        $this->aliases = $response['data']->drush_aliases;
    }

    /**
     * @return MachineTokens
     */
    public function getMachineTokens()
    {
        if (empty($this->machine_tokens)) {
            $this->getContainer()
                ->add('MachineTokens', MachineTokens::class)
                ->addArgument(['user' => $this]);
            $this->machine_tokens = $this->getContainer()->get('MachineTokens');
        }
        return $this->machine_tokens;
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getProfile()->get('full_name');
    }

    /**
     * @return UserOrganizationMemberships
     */
    public function getOrganizationMemberships()
    {
        if (empty($this->org_memberships)) {
            $this->getContainer()
                ->add('UserOrganizationMemberships', UserOrganizationMemberships::class)
                ->addArgument(['user' => $this]);
            $this->org_memberships = $this->getContainer()
                ->get('UserOrganizationMemberships');
        }
        return $this->org_memberships;
    }

    /**
     * @return PaymentMethods
     */
    public function getPaymentMethods()
    {
        if (empty($this->payment_methods)) {
            $this->getContainer()
                ->add('PaymentMethods', PaymentMethods::class)
                ->addArgument(['user' => $this]);
            $this->payment_methods = $this->getContainer()->get('PaymentMethods');
        }
        return $this->payment_methods;
    }

    /**
     * @return string[]
     */
    public function getReferences()
    {
        return [$this->id, $this->getName(), $this->get('email')];
    }

    /**
     * @return UserSiteMemberships
     */
    public function getSiteMemberships()
    {
        if (empty($this->site_memberships)) {
            $this->getContainer()
                ->add('UserSiteMemberships', UserSiteMemberships::class)
                ->addArgument(['user' => $this]);
            $this->site_memberships = $this->getContainer()->get('UserSiteMemberships');
        }
        return $this->site_memberships;
    }

    /**
     * @return SSHKeys
     */
    public function getSSHKeys()
    {
        if (empty($this->ssh_keys)) {
            $this->getContainer()
                ->add('SshKeys', SSHKeys::class)
                ->addArgument(['user' => $this]);
            $this->ssh_keys = $this->getContainer()->get('SshKeys');
        }
        return $this->ssh_keys;
    }

    /**
     * @return Upstreams
     */
    public function getUpstreams()
    {
        if (empty($this->upstreams)) {
            $this->getContainer()
                ->add('Upstreams', Upstreams::class)
                ->addArgument(['user' => $this]);
            $this->upstreams = $this->getContainer()->get('Upstreams');
        }
        return $this->upstreams;
    }

    /**
     * @return Workflows
     */
    public function getWorkflows()
    {
        if (empty($this->workflows)) {
            $this->getContainer()
                ->add('WorkFlows', Workflows::class)
                ->addArgument(['user' => $this]);
            $this->workflows = $this->getContainer()->get('Workflows');
        }
        return $this->workflows;
    }

    /**
     * Formats User object into an associative array for output
     *
     * @return array $data associative array of data for output
     */
    public function serialize()
    {
        $profile = $this->getProfile();
        return [
            'firstname' => $profile->get('firstname'),
            'lastname' => $profile->get('lastname'),
            'email' => $this->get('email'),
            'id' => $this->id,
        ];
    }
}
