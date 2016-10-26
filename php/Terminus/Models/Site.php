<?php

namespace Terminus\Models;

use Terminus\Collections\Branches;
use Terminus\Collections\Environments;
use Terminus\Collections\SiteAuthorizations;
use Terminus\Collections\SiteOrganizationMemberships;
use Terminus\Collections\SiteUserMemberships;
use Terminus\Collections\Workflows;
use Terminus\Config;
use Terminus\Exceptions\TerminusException;

class Site extends TerminusModel
{
    /**
     * @var SiteAuthorizations
     */
    public $authorizations;
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
     * @var Upstream
     */
    public $upstream;
    /**
     * @var SiteUserMemberships
     */
    public $user_memberships;
    /**
     * @var Workflows
     */
    public $workflows;
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

        $params = ['site' => $this,];
        $this->authorizations = new SiteAuthorizations($params);
        $this->branches = new Branches($params);
        $this->environments = new Environments($params);
        $this->new_relic = new NewRelic(null, $params);
        $this->org_memberships = new SiteOrganizationMemberships($params);
        $this->redis = new Redis(null, $params);
        $this->solr = new Solr(null, $params);
        $this->user_memberships = new SiteUserMemberships($params);
        $this->workflows = new Workflows($params);
        $this->setUpstream($attributes);
    }

    /**
     * Adds payment instrument of given site
     *
     * @param string $instrument_id UUID of new payment instrument
     * @return Workflow
     */
    public function addInstrument($instrument_id)
    {
        $args = ['site' => $this->id, 'params' => compact('instrument_id'),];
        return $this->workflows->create('associate_site_instrument', $args);
    }

    /**
     * Completes a site migration in progress
     *
     * @return Workflow
     */
    public function completeMigration()
    {
        return $this->workflows->create('complete_migration');
    }

    /**
     * Converges all bindings on a site
     *
     * @return Workflow
     */
    public function converge()
    {
        return $this->workflows->create('converge_site');
    }

    /**
     * Provides Pantheon Dashboard URL for this site
     *
     * @return string
     */
    public function dashboardUrl()
    {
        $url = sprintf(
            '%s://%s/sites/%s',
            Config::get('dashboard_protocol'),
            Config::get('dashboard_host'),
            $this->id
        );

        return $url;
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
        return $this->workflows->create(
            'deploy_product',
            ['params' => ['product_id' => $upstream_id,],]
        );
    }

    /**
     * Fetches this object from Pantheon
     *
     * @param array $options params to pass to url request
     * @return Site
     */
    public function fetch(array $options = [])
    {
        $data = $this->request->request($this->url)['data'];
        $this->setUpstream($data);
        $this->attributes = (object)array_merge((array)$this->attributes, (array)$data);
        return $this;
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
            $response = $this->request->request("sites/{$this->id}/features");
            $this->features = (array)$response['data'];
        }
        if (isset($this->features[$feature])) {
            return $this->features[$feature];
        }
        return null;
    }

    /**
     * Returns all organization members of this site
     *
     * @return SiteOrganizationMembership[]
     */
    public function getOrganizations()
    {
        $memberships = $this->org_memberships->all();
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
     * Removes this site's payment instrument
     *
     * @return Workflow
     */
    public function removeInstrument()
    {
        return $this->workflows->create('disassociate_site_instrument', ['site' => $this->id,]);
    }

    /**
     * Formats the Site object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        $data = [
            'id'            => $this->id,
            'name'          => $this->get('name'),
            'label'         => $this->get('label'),
            'created'       => date(Config::get('date_format'), $this->get('created')),
            'framework'     => $this->get('framework'),
            'organization'  => $this->get('organization'),
            'service_level' => $this->get('service_level'),
            'upstream'      => (string)$this->upstream,
            'php_version'   => $this->get('php_version'),
            'holder_type'   => $this->get('holder_type'),
            'holder_id'     => $this->get('holder_id'),
            'owner'         => $this->get('owner'),
        ];
        if ($this->has('frozen')) {
            $data['frozen'] = true;
        }
        if (!is_null($data['php_version'])) {
            $data['php_version'] = substr($data['php_version'], 0, 1)
              . '.' . substr($data['php_version'], 1, 1);
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
        return $this->workflows->create('promote_site_user_to_owner', ['params' => compact('user_id'),]);
    }

    /**
     * Update service level
     *
     * @param string $service_level Level to set service on site to
     * @return Workflow
     * @throws TerminusException
     */
    public function updateServiceLevel($service_level)
    {
        try {
            return $this->workflows->create(
                'change_site_service_level',
                ['params' => compact('service_level'),]
            );
        } catch (\Exception $e) {
            if ($e->getCode() == '403') {
                throw new TerminusException('An instrument is required to increase the service level of this site.');
            }
            throw $e;
        }
    }

    /**
     * Modify response data between fetch and assignment
     *
     * @param object $data attributes received from API response
     * @return object $data
     */
    protected function parseAttributes($data)
    {
        if (property_exists($data, 'php_version')) {
            $data->php_version = substr($data->php_version, 0, 1) . '.' . substr($data->php_version, 1, 1);
        }
        return $data;
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
        $this->upstream = new Upstream($upstream_data, ['site' => $this,]);
    }
}
