<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Pantheon\Terminus\Collections\Branches;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class Site
 * @package Pantheon\Terminus\Models
 */
class Site extends TerminusModel implements ConfigAwareInterface, ContainerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;

    /**
     * @var Branches
     */
    public $branches;
    /**
     * @var Environments
     */
    public $environments;
    /**
     * @var NewRelic
     */
    public $new_relic;
    /**
     * @var SiteOrganizationMemberships
     */
    public $org_memberships;
    /**
     * @var Redis
     */
    public $redis;
    /**
     * @var Solr
     */
    public $solr;
    /**
     * @var SiteUserMemberships
     */
    public $user_memberships;
    /**
     * @var Workflows
     */
    public $workflows;
    /**
     * @var \stdClass
     */
    protected $upstream_data;
    /**
     * @var string The URL at which to fetch this model's information
     */
    protected $url;
    /**
     * @var array
     */
    private $features;

    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->url = "sites/{$this->id}?site_state=true";

        $this->setUpstream($attributes);
    }

    /**
     * Add a payment method to the given site
     *
     * @param string $payment_method_id UUID of new payment method
     * @return Workflow
     */
    public function addPaymentMethod($payment_method_id)
    {
        $args = ['site' => $this->id, 'params' => ['instrument_id' => $payment_method_id,],];
        return $this->getWorkflows()->create('associate_site_instrument', $args);
    }

    /**
     * Completes a site migration in progress
     *
     * @return Workflow
     */
    public function completeMigration()
    {
        return $this->getWorkflows()->create('complete_migration');
    }

    /**
     * Converges all bindings on a site
     *
     * @return Workflow
     */
    public function converge()
    {
        return $this->getWorkflows()->create('converge_site');
    }

    /**
     * Provides Pantheon Dashboard URL for this site
     *
     * @return string
     */
    public function dashboardUrl()
    {
        return sprintf(
            '%s://%s/sites/%s',
            $this->getConfig()->get('dashboard_protocol'),
            $this->getConfig()->get('dashboard_host'),
            $this->id
        );
    }

    /**
     * Deletes the site represented by this object
     *
     * @return Workflow
     */
    public function delete()
    {
        $this->request()->request("sites/{$this->id}", ['method' => 'delete',]);
        //TODO: Change this function to use a workflow. The workflow returned always gets 404 on status check.
        //return $this->workflows->create('delete_site');
    }

    /**
     * Creates a new site for migration
     *
     * @param string $upstream_id The UUID for the product to deploy.
     * @return Workflow
     */
    public function deployProduct($upstream_id)
    {
        return $this->getWorkflows()->create('deploy_product', ['params' => ['product_id' => $upstream_id,],]);
    }

    /**
     * Fetches this object from Pantheon
     *
     * @param array $options params to pass to url request
     * @return Site
     */
    public function fetch(array $options = [])
    {
        $data = $this->request()->request($this->url)['data'];
        $this->setUpstream($data);
        $this->attributes = (object)array_merge((array)$this->attributes, (array)$data);
        return $this;
    }

    /**
     * @return Branches
     */
    public function getBranches()
    {
        if (empty($this->branches)) {
            $this->branches = $this->getContainer()->get(Branches::class, [['site' => $this,]]);
        }
        return $this->branches;
    }

    /**
     * @return Environments
     */
    public function getEnvironments()
    {
        if (empty($this->environments)) {
            $this->environments = $this->getContainer()->get(Environments::class, [['site' => $this,]]);
        }
        return $this->environments;
    }

    /**
     * Returns a specific site feature value
     *
     * @param string $feature Feature to check
     * @return mixed|null Feature value, or null if not found
     */
    public function getFeature($feature)
    {
        if (!isset($this->features)) {
            $response = $this->request()->request("sites/{$this->id}/features");
            $this->features = (array)$response['data'];
        }
        if (isset($this->features[$feature])) {
            return $this->features[$feature];
        }
        return null;
    }

    /**
     * Get the human-readable name of the site
     *
     * @return string
     */
    public function getName()
    {
        return $this->get('name');
    }

    /**
     * @return NewRelic
     */
    public function getNewRelic()
    {
        if (empty($this->new_relic)) {
            $this->new_relic = $this->getContainer()->get(NewRelic::class, [null, ['site' => $this,]]);
        }
        return $this->new_relic;
    }

    /**
     * @return SiteOrganizationMemberships
     */
    public function getOrganizationMemberships()
    {
        if (empty($this->user_memberships)) {
            $this->org_memberships = $this->getContainer()->get(SiteOrganizationMemberships::class, [['site' => $this,]]);
        }
        return $this->org_memberships;
    }

    /**
     * Returns all organization members of this site
     *
     * @return SiteOrganizationMembership[]
     */
    public function getOrganizations()
    {
        $memberships = $this->getOrganizationMemberships()->all();
        $orgs = array_combine(
            array_map(
                function ($membership) {
                    return $membership->organization->id;
                },
                $memberships
            ),
            array_map(
                function ($membership) {
                    return $membership->organization;
                },
                $memberships
            )
        );
        return $orgs;
    }

    /**
     * Returns the PHP version of this site.
     *
     * @return null|string
     */
    public function getPHPVersion()
    {
        return !is_null($php_ver = $this->get('php_version')) ? substr($php_ver, 0, 1) . '.' . substr($php_ver, 1) : null;
    }

    /**
     * @return Redis
     */
    public function getRedis()
    {
        if (empty($this->redis)) {
            $this->redis = $this->getContainer()->get(Redis::class, [null, ['site' => $this,]]);
        }
        return $this->redis;
    }

    /**
     * @return Solr
     */
    public function getSolr()
    {
        if (empty($this->solr)) {
            $this->solr = $this->getContainer()->get(Solr::class, [null, ['site' => $this,]]);
        }
        return $this->solr;
    }

    /**
     * @return Upstream
     */
    public function getUpstream()
    {
        return $this->getContainer()->get(Upstream::class, [$this->upstream_data, ['site' => $this,]]);
    }

    /**
     * @return SiteUserMemberships
     */
    public function getUserMemberships()
    {
        if (empty($this->user_memberships)) {
            $this->user_memberships = $this->getContainer()->get(SiteUserMemberships::class, [['site' => $this,]]);
        }
        return $this->user_memberships;
    }

    /**
     * @return Workflows
     */
    public function getWorkflows()
    {
        if (empty($this->workflows)) {
            $this->workflows = $this->getContainer()->get(Workflows::class, [['site' => $this,]]);
        }
        return $this->workflows;
    }

    /**
     * Remove this site's payment method
     *
     * @return Workflow
     */
    public function removePaymentMethod()
    {
        return $this->getWorkflows()->create('disassociate_site_instrument', ['site' => $this->id,]);
    }

    /**
     * Formats the Site object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        $data = [
            'id' => $this->id,
            'name' => $this->get('name'),
            'label' => $this->get('label'),
            'created' => date($this->getConfig()->get('date_format'), $this->get('created')),
            'framework' => $this->get('framework'),
            'organization' => $this->get('organization'),
            'service_level' => $this->get('service_level'),
            'upstream' => (string)$this->getUpstream(),
            'php_version' => $this->getPHPVersion(),
            'holder_type' => $this->get('holder_type'),
            'holder_id' => $this->get('holder_id'),
            'owner' => $this->get('owner'),
            'frozen' => is_null($this->get('frozen')) ? 'false' : 'true',
        ];
        if (isset($this->tags)) {
            $data['tags'] = implode(',', $this->tags->ids());
        }
        if (isset($this->memberships)) {
            $data['memberships'] = implode(',', $this->memberships);
        }
        return $data;
    }

    /**
     * Sets the site owner to the indicated team member
     *
     * @param User $user_id UUID of new owner of site
     * @return Workflow
     * @throws TerminusException
     */
    public function setOwner($user_id)
    {
        return $this->getWorkflows()->create('promote_site_user_to_owner', ['params' => compact('user_id'),]);
    }

    /**
     * Update service level
     *
     * @param string $service_level Level to set service on site to
     * @return Workflow
     * @throws TerminusException|\Exception
     */
    public function updateServiceLevel($service_level)
    {
        try {
            return $this->getWorkflows()->create('change_site_service_level', ['params' => compact('service_level'),]);
        } catch (\Exception $e) {
            if ($e->getCode() == 403) {
                throw new TerminusException('A payment method is required to increase the service level of this site.');
            }
            throw $e;
        }
    }

    /**
     * Ensures the proper creation of an Upstream object
     *
     * @param object $attributes Data about the site from the API
     */
    private function setUpstream($attributes)
    {
        $upstream_data = (object)[];
        if (isset($attributes->settings->upstream)) {
            $upstream_data = $attributes->settings->upstream;
        } else if (isset($attributes->upstream)) {
            $upstream_data = $attributes->upstream;
        }
        $this->upstream_data = $upstream_data;
    }
}
