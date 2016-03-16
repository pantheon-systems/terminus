<?php

namespace Terminus\Models;

use Terminus\Caches\SitesCache;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Organization;
use Terminus\Models\TerminusModel;
use Terminus\Models\Collections\Environments;
use Terminus\Models\Collections\OrganizationSiteMemberships;
use Terminus\Models\Collections\SiteAuthorizations;
use Terminus\Models\Collections\SiteOrganizationMemberships;
use Terminus\Models\Collections\SiteUserMemberships;
use Terminus\Models\Collections\Workflows;

class Site extends TerminusModel {
  /**
   * @var array
   * @todo Use Bindings collection?
   */
  public $bindings;

  /**
   * @var array
   */
  protected $authorizations;

  /**
   * @var Environments
   */
  protected $environments;

  /**
   * @var SiteOrganizationMemberships
   */
  protected $org_memberships;

  /**
   * @var SiteUserMemberships
   */
  protected $user_memberships;

  /**
   * @var Workflows
   */
  protected $workflows;

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
   * @param array  $options    Options to set as $this->key
   */
  public function __construct($attributes = null, array $options = []) {
    if ($attributes == null) {
      $attributes = new \stdClass();
    }
    $must_haves = [
      'name',
      'id',
      'service_level',
      'framework',
      'created',
      'memberships'
    ];
    foreach ($must_haves as $must_have) {
      if (!isset($attributes->$must_have)) {
        $attributes->$must_have = null;
      }
    }
    parent::__construct($attributes, $options);

    $params                 = ['site' => $this,];
    $this->authorizations   = new SiteAuthorizations($params);
    $this->environments     = new Environments($params);
    $this->org_memberships  = new SiteOrganizationMemberships($params);
    $this->user_memberships = new SiteUserMemberships($params);
    $this->workflows        = new Workflows(['owner' => $this,]);
  }

  /**
   * Adds payment instrument of given site
   *
   * @param string $uuid UUID of new payment instrument
   * @return Workflow
   */
  public function addInstrument($uuid) {
    $args     = [
      'site'   => $this->get('id'),
      'params' => ['instrument_id' => $uuid,],
    ];
    $workflow = $this->workflows->create('associate_site_instrument', $args);
    return $workflow;
  }

  /**
   * Adds a tag to the site
   *
   * @param string $tag    Name of tag to apply
   * @param string $org_id Organization to add the tag association to
   * @return array
   */
  public function addTag($tag, $org_id) {
    if ($this->hasTag($tag, $org_id)) {
      $message  = 'This site already has the tag {tag} ';
      $message .= 'associated with the organization {org}.';
      throw new TerminusException(
        $message,
        ['tag' => $tag, 'org' => $org_id,]
      );
    }
    $params    = [$tag => ['sites' => [$this->get('id'),],],];
    $response  = $this->request->request(
      sprintf('organizations/%s/tags', $org_id),
      ['method' => 'put', 'form_params' => $params,]
    );
    return $response;
  }

  /**
   * Apply upstream updates
   *
   * @param string $env_id   Environment name
   * @param bool   $updatedb True to run update.php
   * @param bool   $xoption  True to automatically resolve merge conflicts
   * @return Workflow
   */
  public function applyUpstreamUpdates(
    $env_id,
    $updatedb = true,
    $xoption = false
  ) {
    $params = ['updatedb' => $updatedb, 'xoption' => $xoption];

    $workflow = $this->workflows->create(
      'apply_upstream_updates',
      ['environment' => $env_id, 'params' => $params,]
    );
    return $workflow;
  }

  /**
   * Returns an array of attributes
   *
   * @return \stdClass
   */
  public function attributes() {
    $path     = sprintf('sites/%s/attributes', $this->get('id'));
    $options  = ['method' => 'get',];
    $response = $this->request->request($path, $options);
    return $response['data'];
  }

  /**
   * Converges all bindings on a site
   *
   * @return array
   */
  public function convergeBindings() {
    $response = $this->request->request(
      'sites/' . $this->get('id') . '/converge',
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
      $this->get('id')
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
      'sites/' . $this->get('id'),
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
   * Delete a multidev environment
   *
   * @param string $env           Name of environment to remove
   * @param bool   $delete_branch True to delete branch
   * @return Workflow
   */
  public function deleteEnvironment($env, $delete_branch) {
    $workflow = $this->workflows->create(
      'delete_cloud_development_environment',
      [
        'params' => [
          'environment_id' => $env,
          'delete_branch'  => $delete_branch,
        ],
      ]
    );
    return $workflow;
  }

  /**
   * Deletes site from cache
   *
   * @return void
   */
  public function deleteFromCache() {
    // TODO: $this->collection is not defined.
    $this->collection->deleteSiteFromCache($this->get('name'));
  }

  /**
   * Disables Redis caching
   *
   * @return array
   */
  public function disableRedis() {
    $response = $this->request->request(
      'sites/' . $this->get('id') . '/settings',
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
      'sites/' . $this->get('id') . '/settings',
      ['method' => 'put', 'form_params' => ['allow_indexserver' => false]]
    );
    $this->convergeBindings();
    return $response['data'];
  }

  /**
   * Enables Redis caching
   *
   * @return array
   */
  public function enableRedis() {
    $response = $this->request->request(
      'sites/' . $this->get('id') . '/settings',
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
      'sites/' . $this->get('id') . '/settings',
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
    $response         = $this->request->request(
      sprintf('sites/%s?site_state=true', $this->get('id'))
    );
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
      sprintf('sites/%s/settings', $this->get('id'))
    );
    $this->attributes = $response['data'];
    $this->collection->sites_cache->update((array)$response['data']);
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
        sprintf('sites/%s/features', $this->get('id'))
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
    $orgs = $this->org_memberships->all();
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
   *
   * @param string $org_id UUID of organization site belongs to
   * @return string[]
   */
  public function getTags($org_id) {
    if (isset($this->tags)) {
      return $this->tags;
    }
    $org_site_member = new OrganizationSiteMemberships(
      ['organization' => new Organization(null, ['id' => $org_id,]),]
    );
    $org_site_member->fetch();
    $org  = $org_site_member->get($this->get('id'));
    $tags = $org->get('tags');
    return $tags;
  }

  /**
   * Just the code branches
   *
   * @return array
   */
  public function getTips() {
    $path     = sprintf('sites/%s/code-tips', $this->get('id'));
    $options  = ['method' => 'get',];
    $data     = $this->request->request($path, $options);
    $branches = array_keys((array)$data['data']);
    return $branches;
  }

  /**
   * Get upstream updates
   *
   * @return \stdClass
   */
  public function getUpstreamUpdates() {
    $response = $this->request->request(
      'sites/' . $this->get('id') .  '/code-upstream-updates'
    );
    return $response['data'];
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
   * Imports a full-site archive
   *
   * @param string $url URL to import data from
   * @return Workflow
   */
  public function import($url) {
    $params = [
      'url'      => $url,
      'code'     => 1,
      'database' => 1,
      'files'    => 1,
      'updatedb' => 1,
    ];

    $workflow = $this->workflows->create(
      'do_import',
      ['environment' => 'dev', 'params' => $params,]
    );
    return $workflow;
  }

  /**
   * Load site info
   *
   * @param string $key Set to retrieve a specific attribute as named
   * @return array|null|mixed
   *   If $key is supplied, return named bit of info, or null if not found.
   *   If no $key supplied, return entire info array.
   */
  public function info($key = null) {
    $info = [
      'id'            => $this->get('id'),
      'name'          => null,
      'label'         => null,
      'created'       => null,
      'framework'     => null,
      'organization'  => null,
      'service_level' => null,
      'upstream'      => null,
      'php_version'   => null,
      'holder_type'   => null,
      'holder_id'     => null,
      'owner'         => null,
    ];
    foreach ($info as $info_key => $datum) {
      if ($datum == null) {
        $info[$info_key] = $this->get($info_key);
      }
    }
    if ($info['php_version'] == '55') {
      $info['php_version'] = '5.5';
    } else {
      $info['php_version'] = '5.3';
    }

    if ($key) {
      if (isset($info[$key])) {
        return $info[$key];
      } else {
        return null;
      }
    } else {
      return $info;
    }
  }

  /**
   * Retrieve New Relic Info
   *
   * @return \stdClass
   */
  public function newRelic() {
    $response = $this->request->request(
      'sites/' . $this->get('id') . '/new-relic'
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
    $args     = ['site' => $this->get('id'),];
    $workflow = $this->workflows->create('disassociate_site_instrument', $args);
    return $workflow;
  }

  /**
   * Removes a tag to the site
   *
   * @param string $tag    Tag to remove
   * @param string $org_id Organization to remove the tag association from
   * @return array
   */
  public function removeTag($tag, $org_id) {
    $response = $this->request->request(
      sprintf(
        'organizations/%s/tags/%s/sites?entity=%s',
        $org_id,
        $tag,
        $this->get('id')
      ),
      ['method' => 'delete',]
    );
    return $response;
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
      ['params' => ['user_id' => $new_owner->get('id'),],]
    );
    return $workflow;
  }

  /**
   * Sets the PHP version number of this site
   * Note: Once this changes, you need to refresh the data in the cache for
   *   this site or the returned PHP version will not reflect the change.
   *   $this->fetchAttributes() will complete this action for you.
   *
   * @param string $version_number The version number to set this site to use
   * @return void
   */
  public function setPhpVersion($version_number) {
    $options  = [
      'params' => ['key' => 'php_version', 'value' => $version_number,],
    ];
    $workflow = $this->workflows->create('update_site_setting', $options);
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
