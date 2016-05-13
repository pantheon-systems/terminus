<?php

namespace Terminus\Models;

use Terminus\Exceptions\TerminusException;
use Terminus\Models\Collections\Bindings;
use Terminus\Models\Collections\Environments;
use Terminus\Models\Collections\OrganizationSiteMemberships;
use Terminus\Models\Collections\SiteAuthorizations;
use Terminus\Models\Collections\SiteOrganizationMemberships;
use Terminus\Models\Collections\SiteUserMemberships;
use Terminus\Models\Collections\Workflows;

class Site extends NewModel {
  /**
   * @var array
   */
  public $authorizations;
  /**
   * @var Bindings
   */
  public $bindings;
  /**
   * @var Environments
   */
  public $environments;
  /**
   * @var SiteOrganizationMemberships
   */
  public $org_memberships;
  /**
   * @var SiteUserMemberships
   */
  public $user_memberships;
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
   * @TODO Break this out into its own collection
   */
  private $tags;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   * @return Site
   */
  public function __construct($attributes = null, array $options = []) {
    parent::__construct($attributes, $options);

    $params                 = ['site' => $this,];
    $this->authorizations   = new SiteAuthorizations($params);
    $this->bindings         = new Bindings($params);
    $this->environments     = new Environments($params);
    $this->org_memberships  = new SiteOrganizationMemberships($params);
    $this->user_memberships = new SiteUserMemberships($params);
    $this->workflows        = new Workflows(['owner' => $this,]);
    $this->url              = "sites/{$this->id}?site_state=true";
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
   * Completes a site migration in progress
   *
   * @return Workflow
   */
  public function completeMigration() {
    $workflow = $this->workflows->create('complete_migration');
    return $workflow;
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
   * Disables Redis caching
   *
   * @return array
   */
  public function disableRedis() {
    $response = $this->request->request(
      'sites/' . $this->get('id') . '/settings',
      ['method' => 'put', 'form_params' => ['allow_cacheserver' => false]]
    );
    $this->bindings->converge();
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
    $this->bindings->converge();
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
    $this->bindings->converge();
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
    $this->bindings->converge();
    return $response['data'];
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
    $this->attributes = (object)array_merge(
      (array)$this->attributes,
      $this->parseAttributes((array)$response['data'])
    );
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
   * @return Organization[]
   */
  public function getOrganizations() {
    $this->org_memberships->fetch();
    $organizations = array_combine(
      array_map(
        function($membership) {return $membership->organization->id;},
        $this->org_memberships->all()
      ),
      array_map(
        function($membership) {return $membership->organization;},
        $this->org_memberships->all()
      )
    );
    return $organizations;
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
   * Lists user memberships for this site
   *
   * @return User[]
   */
  public function getUsers() {
    $this->user_memberships->fetch();
    $users = array_combine(
      array_map(
        function($membership) {return $membership->user->id;},
        $this->user_memberships->all()
      ),
      array_map(
        function($membership) {return $membership->user;},
        $this->user_memberships->all()
      )
    );
    return $users;
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
    if (!is_null($info['created']) && is_numeric($info['created'])) {
      $info['created'] = date(TERMINUS_DATE_FORMAT, $info['created']);
    }
    if ((boolean)$this->get('frozen')) {
      $info['frozen'] = true;
    }
    $info['php_version'] = substr($info['php_version'], 0, 1)
      . '.' . substr($info['php_version'], 1, 1);

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
    $org_ids       = $this->org_memberships->fetch()->ids();
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
    $new_owner = $this->user_memberships->fetch()->get($owner);
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
   * Sets the PHP version number of this site
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

}
