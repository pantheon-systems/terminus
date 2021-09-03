<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Friends\LocalCopiesTrait;
use Pantheon\Terminus\Friends\OrganizationsInterface;
use Pantheon\Terminus\Friends\OrganizationsTrait;
use Pantheon\Terminus\Collections\Branches;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Collections\Plans;
use Pantheon\Terminus\Collections\SiteAuthorizations;
use Pantheon\Terminus\Collections\SiteMetrics;
use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class Site
 * @package Pantheon\Terminus\Models
 */
class Site extends TerminusModel implements ContainerAwareInterface, OrganizationsInterface
{
    use ContainerAwareTrait;
    use OrganizationsTrait;
    use LocalCopiesTrait;

    /**
     *
     */
    const PRETTY_NAME = 'site';

    /**
     * @var array
     */
    public static $date_attributes = ['created', 'last_frozen_at',];
    /**
     * @var string
     */
    protected $url = 'sites/{id}?site_state=true';
    /**
     * @var Branches
     */
    protected $branches;
    /**
     * @var Environments
     */
    protected $environments;
    /**
     * @var NewRelic
     */
    protected $new_relic;
    /**
     * @var SiteOrganizationMemberships
     */
    protected $org_memberships;
    /**
     * @var Plan
     */
    protected $plan;
    /**
     * @var Plans
     */
    protected $plans;
    /**
     * @var Redis
     */
    protected $redis;
    /**
     * @var Solr
     */
    protected $solr;
    /**
     * @var SiteUserMemberships
     */
    protected $user_memberships;
    /**
     * @var SiteAuthorizations
     */
    private $authorizations;
    /**
     * @var array
     */
    private $features;
    /**
     * @var Workflows
     */
    private $workflows;

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
     * Provides Pantheon Dashboard URL for this site
     *
     * @return string
     */
    public function dashboardUrl()
    {
        $config = $this->getConfig();
        return "{$config->get('dashboard_protocol')}://{$config->get('dashboard_host')}/sites/{$this->id}";
    }

    /**
     * Deletes the site represented by this object
     *
     * @return Workflow
     * @throws TerminusException
     */
    public function delete()
    {
        return $this->getWorkflows()->create('delete_site');
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
     * @return SiteAuthorizations
     */
    public function getAuthorizations()
    {
        if (empty($this->authorizations)) {
            $this->authorizations = new SiteAuthorizations(['site' => $this]);
            $this->getContainer()->inflect($this->authorizations);
        }
        return $this->authorizations;
    }

    /**
     * @return Branches
     */
    public function getBranches()
    {
        if (empty($this->branches)) {
            $this->branches = new Branches(['site' => $this]);
            $this->getContainer()->inflect($this->branches);
        }
        return $this->branches;
    }

    /**
     * Reset our environments cache. This may be necessary after calling
     * $site->getEnvironments()->create($to_env_id, $from_env), as Terminus
     * will not have any information about the new environment in its cache.
     */
    public function unsetEnvironments()
    {
        unset($this->environments);
    }

    /**
     * @return Environments
     */
    public function getEnvironments(): Environments
    {
        if (empty($this->environments)) {
            $this->environments = new Environments(['site' => $this]);
            $this->getContainer()->inflect($this->environments);
        }
        return $this->environments;
    }

    /**
     * Returns a specific site feature value
     *
     * @param string $feature Feature to check
     *
     * @return mixed|null Feature value, or null if not found
     * @throws \Exception
     */
    public function getFeature($feature)
    {
        if (!isset($this->features)) {
            try {
                $response = $this->request()->request("sites/{$this->id}/features");
                $this->features = (array)$response['data'];
            } catch (\Exception $e) {
                if ($e->getCode() == 404) {
                    return null;
                }

                throw $e;
            }
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
            $this->new_relic = new NewRelic(null, ['site' => $this]);
            $this->getContainer()->inflect($this->new_relic);
        }
        return $this->new_relic;
    }

    /**
     * @return SiteOrganizationMemberships
     */
    public function getOrganizationMemberships()
    {
        if (empty($this->user_memberships)) {
            $this->org_memberships = new SiteOrganizationMemberships(['site' => $this]);
            $this->getContainer()->inflect($this->org_memberships);
        }
        return $this->org_memberships;
    }

    /**
     * @return Plan
     */
    public function getPlan()
    {
        if (empty($this->plan)) {
            $this->plan = new Plan(['site' => $this]);
            $this->getContainer()->inflect($this->plan);
        }
        return $this->plan;
    }

    /**
     * @return Plans
     */
    public function getPlans()
    {
        if (empty($this->plans)) {
            $this->plans = new plans(null, ['site' => $this]);
            $this->getContainer()->inflect($this->plans);
        }
        return $this->plans;
    }

    /**
     * @return Redis
     */
    public function getRedis()
    {
        if (empty($this->redis)) {
            $this->redis = new Redis(null, ['site' => $this]);
            $this->getContainer()->inflect($this->redis);
        }
        return $this->redis;
    }

    /**
     * @return array
     */
    public function getReferences()
    {
        return [$this->id, $this->getName(), $this->get('label'),];
    }

    /**
     * @return SiteMetrics
     */
    public function getSiteMetrics()
    {
        if (empty($this->site_metrics)) {
            $this->site_metrics = new SiteMetrics(['site' => $this]);
            $this->getContainer()->inflect($this->site_metrics);
        }
        return $this->site_metrics;
    }

    /**
     * @return Solr
     */
    public function getSolr()
    {
        if (empty($this->solr)) {
            $this->solr = new Solr(null, ['site' => $this]);
            $this->getContainer()->inflect($this->solr);
        }
        return $this->solr;
    }

    /**
     * @return Upstream
     */
    public function getUpstream()
    {
        $upstream_data = (object)array_merge((array)$this->get('upstream'), (array)$this->get('product'));
        if (empty((array)$upstream_data)
            && !is_null($settings = $this->get('settings'))
            && isset($settings->upstream)
        ) {
            $upstream_data = $settings->upstream;
        }
        $siteUpstream = new SiteUpstream($upstream_data, ['site' => $this]);
        $this->getContainer()->inflect($siteUpstream);
        return $siteUpstream;
    }

    /**
     * @return SiteUserMemberships
     */
    public function getUserMemberships()
    {
        if (empty($this->user_memberships)) {
            $this->user_memberships = new SiteUserMemberships(['site' => $this]);
            $this->getContainer()->inflect($this->user_memberships);
        }
        return $this->user_memberships;
    }

    /**
     * @return Workflows
     */
    public function getWorkflows()
    {
        if (empty($this->workflows)) {
            $this->workflows = new Workflows(['site' => $this]);
            $this->getContainer()->inflect($this->workflows);
        }
        return $this->workflows;
    }

    /**
     * Returns whether the site is frozen or not.
     *
     * @return boolean
     */
    public function isFrozen()
    {
        return !empty($this->get('frozen'));
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
        $settings = $this->get('settings');

        $data = [
            'id' => $this->id,
            'name' => $this->get('name'),
            'label' => $this->get('label'),
            'created' => $this->get('created'),
            'framework' => $this->get('framework'),
            'organization' => $this->get('organization'),
            'plan_name' => $this->get('plan_name'),
            'max_num_cdes' => $settings ? $settings->max_num_cdes : 0,
            'upstream' => (string)$this->getUpstream(),
            'holder_type' => $this->get('holder_type'),
            'holder_id' => $this->get('holder_id'),
            'owner' => $this->get('owner'),
            'region' => $this->get('preferred_zone_label'),
            'frozen' => $this->isFrozen(),
            'last_frozen_at' => $this->get('last_frozen_at'),
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
     * Creates a new site for migration
     *
     * @param string $upstream_id The UUID for the product to deploy.
     * @return Workflow
     */
    public function setUpstream($upstream_id)
    {
        return $this->getWorkflows()->create('switch_upstream', ['params' => ['upstream_id' => $upstream_id,],]);
    }

    /**
     * Update service level
     *
     * @deprecated 2.0.0 This is no longer the appropriate way to change a site's plan. Use $this->getPlans()->set().
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
     * @return bool
     */
    public function valid():bool
    {
        return (bool) $this->id;
    }

    /**
     * @return string
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function getLocalCopyFolder(): string
    {
        $local_copy_folder = $this->getLocalCopiesFolder() . DIRECTORY_SEPARATOR . $this->getName();
        if (!is_dir($local_copy_folder)) {
            mkdir($local_copy_folder);
            if (!is_dir($local_copy_folder)) {
                throw new TerminusException(
                    "Cannot create local copy folder for site: {site}",
                    ['site' => $this->getName()]
                );
            }
        }
        return $local_copy_folder;
    }

    public function __toString()
    {
        return $this->getName();
    }
}
