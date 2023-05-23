<?php

namespace Pantheon\Terminus\Models;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\Branches;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Collections\Plans;
use Pantheon\Terminus\Collections\SiteAuthorizations;
use Pantheon\Terminus\Collections\SiteMetrics;
use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Friends\LocalCopiesTrait;
use Pantheon\Terminus\Friends\OrganizationsInterface;
use Pantheon\Terminus\Friends\OrganizationsTrait;
use Pantheon\Terminus\Helpers\Utility\SiteFramework;

/**
 * Class Site
 *
 * @package Pantheon\Terminus\Models
 */
class Site extends TerminusModel implements
    ContainerAwareInterface,
    OrganizationsInterface
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
     * @var SiteOrganizationMemberships
     *
     * Set by SiteJoinTrait.
     */
    public $memberships;

    /**
     * @var Pantheon\Terminus\Collections\Tags
     */
    public $tags;

    /**
     * @var string
     */
    public $framework;

    /**
     * Add a payment method to the given site
     *
     * @param string $payment_method_id UUID of new payment method
     *
     * @return Workflow
     */
    public function addPaymentMethod($payment_method_id)
    {
        $args = [
            'site' => $this->id,
            'params' => ['instrument_id' => $payment_method_id,],
        ];
        return $this->getWorkflows()->create(
            'associate_site_instrument',
            $args
        );
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
     *
     * @return Workflow
     */
    public function deployProduct($upstream_id)
    {
        return $this->getWorkflows()->create(
            'deploy_product',
            ['params' => ['product_id' => $upstream_id,],]
        );
    }

    /**
     * @return SiteAuthorizations
     */
    public function getAuthorizations()
    {
        if (empty($this->authorizations)) {
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, SiteAuthorizations::class)
                ->addArgument(['site' => $this]);
            $this->authorizations = $this->getContainer()->get($nickname);
        }
        return $this->authorizations;
    }

    /**
     * @return Branches
     */
    public function getBranches()
    {
        if (empty($this->branches)) {
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, Branches::class)
                ->addArgument(['site' => $this]);
            $this->branches = $this->getContainer()->get($nickname);
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
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, Environments::class)
                ->addArgument(['site' => $this]);
            $this->environments = $this->getContainer()->get($nickname);
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
                $response = $this->request()->request(
                    "sites/{$this->id}/features"
                );
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
     * Returns the site framework.
     *
     * @return \Pantheon\Terminus\Helpers\Utility\SiteFramework
     */
    public function getFramework(): SiteFramework
    {
        if (!isset($this->framework)) {
            $this->framework = new SiteFramework($this->get('framework'));
        }

        return $this->framework;
    }

    /**
     * @return NewRelic
     */
    public function getNewRelic()
    {
        if (empty($this->new_relic)) {
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, NewRelic::class)
                ->addArguments([null, ['site' => $this]]);
            $this->new_relic = $this->getContainer()->get($nickname);
        }
        return $this->new_relic;
    }

    /**
     * @return SiteOrganizationMemberships
     */
    public function getOrganizationMemberships()
    {
        if (empty($this->user_memberships)) {
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add(
                $nickname,
                SiteOrganizationMemberships::class
            )
                ->addArgument(['site' => $this]);
            $this->org_memberships = $this->getContainer()->get($nickname);
        }
        return $this->org_memberships;
    }

    /**
     * @return Plan
     */
    public function getPlan()
    {
        if (empty($this->plan)) {
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, Plan::class)
                ->addArguments([null, ['site' => $this]]);
            $this->plan = $this->getContainer()->get($nickname);
        }
        return $this->plan;
    }

    /**
     * @return Plans
     */
    public function getPlans()
    {
        if (empty($this->plans)) {
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, Plans::class)
                ->addArgument(['site' => $this]);
            $this->plans = $this->getContainer()->get($nickname);
        }
        return $this->plans;
    }

    /**
     * @return Redis
     */
    public function getRedis()
    {
        if (empty($this->redis)) {
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, Redis::class)
                ->addArguments([null, ['site' => $this]]);
            $this->redis = $this->getContainer()->get($nickname);
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
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, SiteMetrics::class)
                ->addArgument(['site' => $this]);
            $this->site_metrics = $this->getContainer()->get($nickname);
        }
        return $this->site_metrics;
    }

    /**
     * @return Solr
     */
    public function getSolr()
    {
        if (empty($this->solr)) {
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, Solr::class)
                ->addArguments([null, ['site' => $this]]);
            $this->solr = $this->getContainer()->get($nickname);
        }
        return $this->solr;
    }

    /**
     * Returns the Upstream.
     *
     * @return \Pantheon\Terminus\Models\SiteUpstream
     */
    public function getUpstream(): SiteUpstream
    {
        $upstream_data = (object)array_merge(
            (array)$this->get('upstream'),
            (array)$this->get('product')
        );
        if (empty((array)$upstream_data)
            && !is_null($settings = $this->get('settings'))
            && isset($settings->upstream)
        ) {
            $upstream_data = $settings->upstream;
        }
        $nickname = \uniqid(__FUNCTION__ . "-");
        $this->getContainer()->add($nickname, SiteUpstream::class)
            ->addArguments([$upstream_data, ['site' => $this]]);
        return $this->getContainer()->get($nickname);
    }

    /**
     * @return SiteUserMemberships
     */
    public function getUserMemberships()
    {
        if (empty($this->user_memberships)) {
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, SiteUserMemberships::class)
                ->addArgument(['site' => $this]);
            $this->user_memberships = $this->getContainer()->get($nickname);
        }
        return $this->user_memberships;
    }

    /**
     * @return Workflows
     */
    public function getWorkflows()
    {
        if (empty($this->workflows)) {
            $nickname = \uniqid(__FUNCTION__ . "-");
            $this->getContainer()->add($nickname, Workflows::class)
                ->addArgument(['site' => $this]);
            $this->workflows = $this->getContainer()->get($nickname);
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
        return $this->getWorkflows()->create(
            'disassociate_site_instrument',
            ['site' => $this->id,]
        );
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
            'upstream_label' => $this->getUpstream()->get('label'),
            'holder_type' => $this->get('holder_type'),
            'holder_id' => $this->get('holder_id'),
            'owner' => $this->get('owner'),
            'region' => $this->get('preferred_zone_label'),
            'frozen' => $this->isFrozen(),
            'last_frozen_at' => $this->get('last_frozen_at'),
            'tags' => '',
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
     *
     * @return Workflow
     * @throws TerminusException
     */
    public function setOwner($user_id)
    {
        return $this->getWorkflows()->create(
            'promote_site_user_to_owner',
            ['params' => compact('user_id'),]
        );
    }

    /**
     * Creates a new site for migration
     *
     * @param string $upstream_id The UUID for the product to deploy.
     *
     * @return Workflow
     */
    public function setUpstream($upstream_id)
    {
        return $this->getWorkflows()->create(
            'switch_upstream',
            ['params' => ['upstream_id' => $upstream_id,],]
        );
    }

    /**
     * Update service level
     *
     * @param string $service_level Level to set service on site to
     *
     * @return Workflow
     * @throws TerminusException|\Exception
     * @deprecated 2.0.0 This is no longer the appropriate way to change a
     *     site's plan. Use $this->getPlans()->set().
     *
     */
    public function updateServiceLevel($service_level)
    {
        try {
            return $this->getWorkflows()->create(
                'change_site_service_level',
                ['params' => compact('service_level'),]
            );
        } catch (\Exception $e) {
            if ($e->getCode() == 403) {
                throw new TerminusException(
                    'A payment method is required to increase the service level of this site.'
                );
            }
            throw $e;
        }
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return (bool)$this->id;
    }

    /**
     * Returns the path to the site local copy directory.
     *
     * @param string|null $siteDirName
     *
     * @return string
     *
     * @throws TerminusException
     */
    public function getLocalCopyDir(?string $siteDirName = null): string
    {
        return $this->getLocalCopiesSiteDir($siteDirName ?? $this->getName());
    }

    public function __toString()
    {
        return $this->getName();
    }
}
