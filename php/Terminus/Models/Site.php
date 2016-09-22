<?php

namespace Terminus\Models;

use Terminus\Config;
use Terminus\Exceptions\TerminusException;
use Terminus\Collections\Environments;
use Terminus\Collections\OrganizationSiteMemberships;
use Terminus\Collections\SiteAuthorizations;
use Terminus\Collections\SiteOrganizationMemberships;
use Terminus\Collections\SiteUserMemberships;
use Terminus\Collections\Workflows;

class Site extends TerminusModel {
  /**
   * @var array
   * @todo Use Bindings collection?
   */
  public $bindings;
  /**
   * @var SiteAuthorizations
   */
  public $authorizations;
  /**
   * @var Environments
   */
  public $environments;
  /**
   * @var SiteOrganizationMemberships
   */
  public $org_memberships;
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
   * @var array
   */
  private $tags;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options with which to configure this model
   */
  public function __construct($attributes = null, array $options = []) {
    parent::__construct($attributes, $options);
    $this->url = "sites/{$this->id}?site_state=true";

    $params = ['site' => $this,];
    $this->authorizations   = new SiteAuthorizations($params);
    $this->environments     = new Environments($params);
    $this->org_memberships  = new SiteOrganizationMemberships($params);
    $this->user_memberships = new SiteUserMemberships($params);
    $this->workflows        = new Workflows($params);

    if (isset($attributes->upstream)) {
      $this->upstream = new Upstream($attributes->upstream, $params);
    } else {
      $this->upstream = new Upstream((object)[], $params);
    }
  }

  /**
   * Adds payment instrument of given site
   *
   * @param string $uuid UUID of new payment instrument
   * @return Workflow
   */
  public function addInstrument($uuid) {
    $args     = [
      'site'   => $this->id,
      'params' => ['instrument_id' => $uuid,],
    ];
    $workflow = $this->workflows->create('associate_site_instrument', $args);
    return $workflow;
  }

  /**
   * Adds a tag to the site
   *
   * @param string       $tag Name of tag to apply
   * @param Organization $org Organization to add the tag association to
   * @return array
   */
  public function addTag($tag, $org) {
    if ($this->hasTag($tag, $org)) {
      $message  = 'This site already has the tag {tag} ';
      $message .= 'associated with the organization {org}.';
      throw new TerminusException(
        $message,
        ['tag' => $tag, 'org' => $org->id,]
      );
    }
    $params   = [$tag => ['sites' => [$this->id,],],];
    $response = $this->request->request(
      sprintf('organizations/%s/tags', $org->id),
      ['method' => 'put', 'form_params' => $params,]
    );
    return $response;
  }

  /**
   * Returns an array of attributes
   *
   * @return \stdClass
   */
  public function attributes() {
    $path     = sprintf('sites/%s/attributes', $this->id);
    $options  = ['method' => 'get',];
    $response = $this->request->request($path, $options);
    return $response['data'];
  }

  /**
   * Creates a new site for migration
   *
   * @param string[] $product_id The uuid for the product to deploy.
   * @return Workflow
   */
  public function deployProduct($product_id) {
    $workflow = $this->workflows->create(
      'deploy_product',
      ['params' => ['product_id' => $product_id,],]
    );
    return $workflow;
  }

  /**
   * Completes a site migration in progress
   *
   * @return Workflow
   */
  public function completeMigration() {
    $workflow = $this->workflows->create('complete_migration');
    return $workflow;
  }

  /**
   * Converges all bindings on a site
   *
   * @return array
   */
  public function convergeBindings() {
    $response = $this->request->request(
      'sites/' . $this->id . '/converge',
      ['method' => 'post']
    );
    return $response['data'];
  }

  /**
   * Create a new branch
   *
   * @param string $branch Name of new branch
   * @return Workflow
   */
  public function createBranch($branch) {
    $path     = sprintf(
      'sites/%s/code-branch',
      $this->id
    );
    $options  = [
      'form_params' => ['refspec' => sprintf('refs/heads/%s', $branch),],
      'method'      => 'post',
    ];
    $response = $this->request->request($path, $options);
    return $response['data'];
  }

  /**
   * Deletes site
   *
   * @return array
   */
  public function delete() {
    $response = $this->request->request(
      'sites/' . $this->id,
      ['method' => 'delete',]
    );
    return $response;
  }

  /**
   * Delete a branch from site remove
   *
   * @param string $branch Name of branch to remove
   * @return Workflow
   */
  public function deleteBranch($branch) {
    $workflow = $this->workflows->create(
      'delete_environment_branch',
      ['params' => ['environment_id' => $branch,],]
    );
    return $workflow;
  }

  /**
   * Disables New Relic
   *
   * @param object $site The site object
   * @return bool
   */
  public function disableNewRelic($site) {
    if ($workflow = $site->workflows->create(
      'disable_new_relic_for_site',
      ['site' => $site->id,]
    )) {
      $workflow->wait();
      return true;
    } else {
      return false;
    }
  }

  /**
   * Disables Redis caching
   *
   * @return array
   */
  public function disableRedis() {
    $response = $this->request->request(
      'sites/' . $this->id . '/settings',
      ['method' => 'put', 'form_params' => ['allow_cacheserver' => false]]
    );
    $this->convergeBindings();
    return $response['data'];
  }

  /**
   * Disables Solr indexing
   *
   * @return array
   */
  public function disableSolr() {
    $response = $this->request->request(
      'sites/' . $this->id . '/settings',
      ['method' => 'put', 'form_params' => ['allow_indexserver' => false]]
    );
    $this->convergeBindings();
    return $response['data'];
  }

  /**
   * Enables New Relic
   *
   * @param object $site The site object
   * @return bool
   */
  public function enableNewRelic($site) {
    if ($workflow = $site->workflows->create(
      'enable_new_relic_for_site',
      ['site' => $site->id,]
    )) {
      $workflow->wait();
      return true;
    } else {
      return false;
    }
  }

  /**
   * Enables Redis caching
   *
   * @return array
   */
  public function enableRedis() {
    $response = $this->request->request(
      'sites/' . $this->id . '/settings',
      ['method' => 'put', 'form_params' => ['allow_cacheserver' => true]]
    );
    $this->convergeBindings();
    return $response['data'];
  }

  /**
   * Enables Solr indexing
   *
   * @return array
   */
  public function enableSolr() {
    $response = $this->request->request(
      'sites/' . $this->id . '/settings',
      ['method' => 'put', 'form_params' => ['allow_indexserver' => true]]
    );
    $this->convergeBindings();
    return $response['data'];
  }

  /**
   * Fetches this object from Pantheon
   *
   * @param array $options params to pass to url request
   * @return Site
   */
  public function fetch(array $options = []) {
    $response = $this->request->request($this->url);
    $this->upstream = new Upstream($response['data']->upstream, ['site' => $this,]);
    unset($response['data']->upstream);
    $this->attributes = $response['data'];
    return $this;
  }

  /**
   * Re-fetches site attributes from the API
   *
   * @return void
   */
  public function fetchAttributes() {
    $response = $this->request->request(
      sprintf('sites/%s/settings', $this->id)
    );
    $this->attributes = $response['data'];
  }

  /**
   * Returns given attribute, if present
   *
   * @param string $attribute Name of attribute requested
   * @return mixed|null Attribute value, or null if not found
   */
  public function get($attribute) {
    if (isset($this->attributes->$attribute)) {
      return $this->attributes->$attribute;
    }
    return null;
  }

  /**
   * Returns a specific site feature value
   *
   * @param string $feature Feature to check
   * @return mixed|null Feature value, or null if not found
   */
  public function getFeature($feature) {
    if (!isset($this->features)) {
      $response       = $this->request->request(
        sprintf('sites/%s/features', $this->id)
      );
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
  public function getOrganizations() {
    $memberships = $this->org_memberships->all();
    $users = array_combine(
      array_map(
        function($membership) {
          return $membership->organization->id;
        },
        $memberships
      ),
      array_map(
        function($membership) {
          return $membership->organization;
        },
        $memberships
      )
    );
    return $orgs;
  }

  /**
   * Lists user memberships for this site
   *
   * @return SiteUserMemberships
   */
  public function getSiteUserMemberships() {
    $this->user_memberships = $this->user_memberships->fetch();
    return $this->user_memberships;
  }

  /**
   * Returns tags from the site/org join
   * TODO: Move these into tags model/collection
   *
   * @param Organization $org UUID of organization site belongs to
   * @return string[]
   */
  public function getTags($org) {
    if (isset($this->tags)) {
      return $this->tags;
    }
    $org_site_member = new OrganizationSiteMemberships(
      ['organization' => $org,]
    );
    $org_site_member->fetch();
    $org  = $org_site_member->get($this->id);
    $tags = $org->get('tags');
    return $tags;
  }

  /**
   * Just the code branches
   *
   * @return array
   */
  public function getTips() {
    $path     = sprintf('sites/%s/code-tips', $this->id);
    $options  = ['method' => 'get',];
    $data     = $this->request->request($path, $options);
    $branches = array_keys((array)$data['data']);
    return $branches;
  }

  /**
   * Checks to see whether the site has a tag associated with the given org
   *
   * @param string $tag    Name of tag to check for
   * @param string $org_id Organization with which this tag is associated
   * @return bool
   */
  public function hasTag($tag, $org_id) {
    $tags    = $this->getTags($org_id);
    $has_tag = in_array($tag, $tags);
    return $has_tag;
  }

  /**
   * Retrieve New Relic Info
   *
   * @return \stdClass
   */
  public function newRelic() {
    $response = $this->request->request(
      'sites/' . $this->id . '/new-relic'
    );
    return $response['data'];
  }

  /**
   * Determines if an organization is a member of this site
   *
   * @param string $uuid UUID of organization to check for
   * @return bool True if organization is a member of this site
   */
  public function organizationIsMember($uuid) {
    $org_ids       = $this->org_memberships->ids();
    $org_is_member = in_array($uuid, $org_ids);
    return $org_is_member;
  }

  /**
   * Removes payment instrument of given site
   *
   * @params string $uuid UUID of new payment instrument
   * @return Workflow
   */
  public function removeInstrument() {
    $args     = ['site' => $this->id,];
    $workflow = $this->workflows->create('disassociate_site_instrument', $args);
    return $workflow;
  }

  /**
   * Removes a tag to the site
   *
   * @param string       $tag Tag to remove
   * @param Organization $org Organization to remove the tag association from
   * @return array
   */
  public function removeTag($tag, $org) {
    $response = $this->request->request(
      sprintf(
        'organizations/%s/tags/%s/sites?entity=%s',
        $org->id,
        $tag,
        $this->id
      ),
      ['method' => 'delete',]
    );
    return $response;
  }

    /**
     * Formats the Site object into an associative array for output
     *
     * @return array Associative array of data for output
     */
  public function serialize() {
    $data = [
      'id'            => $this->id,
      'name'          => $this->get('name'),
      'label'         => $this->get('label'),
      'created'       => date(Config::get('date_format'), $this->get('created')),
      'framework'     => $this->get('framework'),
      'organization'  => $this->get('organization'),
      'service_level' => $this->get('service_level'),
      'upstream'      => $this->upstream->serialize(),
      'php_version'   => $this->get('php_version'),
      'holder_type'   => $this->get('holder_type'),
      'holder_id'     => $this->get('holder_id'),
      'owner'         => $this->get('owner'),
    ];
    if ((boolean)$this->get('frozen')) {
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
   * @param string $owner UUID of new owner of site
   * @return Workflow
   * @throws TerminusException
   */
  public function setOwner($owner = null) {
    $new_owner = $this->user_memberships->get($owner);
    if ($new_owner == null) {
      $message = 'The owner must be a team member. Add them with `site team`';
      throw new TerminusException($message);
    }
    $workflow = $this->workflows->create(
      'promote_site_user_to_owner',
      ['params' => ['user_id' => $new_owner->id,],]
    );
    return $workflow;
  }

  /**
   * Update service level
   *
   * @param string $level Level to set service on site to
   * @return \stdClass
   * @throws TerminusException
   */
  public function updateServiceLevel($level) {
    try {
      $workflow = $this->workflows->create(
        'change_site_service_level',
        ['params' => ['service_level' => $level]]
      );
    } catch (\Exception $e) {
      if (strpos($e->getMessage(), '403') !== false) {
        throw new TerminusException(
          'Instrument required to increase service level',
          [],
          1
        );
      }
      throw $e;
    }
    return $workflow;
  }

  /**
   * Verifies if the given framework is in use
   *
   * @param string $framework_name Name of framework to verify
   * @return bool
   * @todo This function is unused; remove?
   */
  private function hasFramework($framework_name) {
    $has_framework = ($framework_name == $this->get('framework'));
    return $has_framework;
  }

}
