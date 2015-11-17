<?php

namespace Terminus\Models;

use Terminus;
use TerminusCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Organization;
use Terminus\Models\TerminusModel;
use Terminus\Models\Collections\Environments;
use Terminus\Models\Collections\OrganizationSiteMemberships;
use Terminus\Models\Collections\SiteOrganizationMemberships;
use Terminus\Models\Collections\SiteUserMemberships;
use Terminus\Models\Collections\Workflows;

class Site extends TerminusModel {
  public $bindings;

  protected $environments;
  protected $org_memberships;
  protected $user_memberships;
  protected $workflows;

  private $features;
  private $tags;

  /**
   * Object constructor
   *
   * @param [stdClass] $attributes Attributes of this model
   * @param [array]    $options    Options to set as $this->key
   * @return [Site] $this
   */
  public function __construct($attributes = null, $options = array()) {
    if ($attributes == null) {
      $attributes = new \stdClass();
    }
    $must_haves = array(
      'name',
      'id',
      'service_level',
      'framework',
      'created',
      'memberships'
    );
    foreach ($must_haves as $must_have) {
      if (!isset($attributes->$must_have)) {
        $attributes->$must_have = null;
      }
    }
    parent::__construct($attributes, $options);

    $this->environments     = new Environments(array('site' => $this));
    $this->org_memberships  = new SiteOrganizationMemberships(
      array('site' => $this)
    );
    $this->user_memberships = new SiteUserMemberships(array('site' => $this));
    $this->workflows        = new Workflows(array('owner' => $this));
  }

  /**
   * Adds payment instrument of given site
   *
   * @param [string] $uuid UUID of new payment instrument
   * @return [Workflow] $workflow Workflow object for the request
   */
  public function addInstrument($uuid) {
    $args     = array(
      'site'   => $this->get('id'),
      'params' => array(
        'instrument_id' => $uuid
      )
    );
    $workflow = $this->workflows->create('associate_site_instrument', $args);
    return $workflow;
  }

  /**
   * Adds a tag to the site
   *
   * @param [string] $tag Tag to apply
   * @param [string] $org Organization to add the tag associateion to
   * @return [array] $response
   */
  public function addTag($tag, $org) {
    $params   = array($tag => array('sites' => array($this->get('id'))));
    $response = TerminusCommand::simpleRequest(
      sprintf('organizations/%s/tags', $org),
      array('method' => 'put', 'form_params' => $params)
    );
    return $response;
  }

  /**
   * Apply upstream updates
   *
   * @param [string]  $env_id   Environment name
   * @param [boolean] $updatedb True to run update.php
   * @param [boolean] $xoption  True to automatically resolve merge conflicts
   * @return [Workflow] $workflow
   */
  public function applyUpstreamUpdates(
    $env_id,
    $updatedb = true,
    $xoption = false
  ) {
    $params = array(
      'updatedb' => $updatedb,
      'xoption'  => $xoption
    );

    $workflow = $this->workflows->create(
      'apply_upstream_updates',
      array(
        'environment' => $env_id,
        'params' => $params
      )
    );
    return $workflow;
  }

  /**
   * Returns an array of attributes
   *
   * @return [stdClass] $atts['data']
   */
  public function attributes() {
    $path   = 'attributes';
    $method = 'GET';
    $atts   = TerminusCommand::request(
      'sites',
      $this->get('id'),
      $path,
      $method
    );
    return $atts['data'];
  }

  /**
   * Fetch Binding info
   *
   * @param [string] $type Which sort of binding to retrieve
   * @return [array] $this->bindings
   */
  public function bindings($type = null) {
    if (empty($this->bindings)) {
      $response = TerminusCommand::simpleRequest(
        'sites/' . $this->get('id') . '/' . $bindings
      );
      foreach ($response['data'] as $id => $binding) {
        $binding->id = $id;
        $this->bindings[$binding->type][] = $binding;
      }
    }
    if ($type) {
      if (isset($this->bindings[$type])) {
        return $this->bindings[$type];
      } else {
        return false;
      }
    }
    return $this->bindings;
  }

  /**
   * Create a new branch
   *
   * @param [string] $branch Name of new branch
   * @return [Workflow] $workflow
   */
  public function createBranch($branch) {
    $form_params = array('refspec' => sprintf('refs/heads/%s', $branch));
    $response    = TerminusCommand::request(
      'sites',
      $this->get('id'),
      'code-branch',
      'POST',
      compact('form_params')
    );
    return $response['data'];
  }

  /**
   * Deletes site
   *
   * @return [array] $response
   */
  public function delete() {
    $response = TerminusCommand::simpleRequest(
      'sites/' . $this->get('id'),
      array('method' => 'delete')
    );
    return $response;
  }

  /**
   * Delete a branch from site remove
   *
   * @param [string] $branch Name of branch to remove
   * @return [void]
   */
  public function deleteBranch($branch) {
    $workflow = $this->workflows->create(
      'delete_environment_branch',
      array(
        'params' => array(
          'environment_id' => $branch,
        )
      )
    );
    return $workflow;
  }

  /**
   * Delete a multidev environment
   *
   * @param [string]  $env           Name of environment to remove
   * @param [boolean] $delete_branch True to delete branch
   * @return [void]
   */
  public function deleteEnvironment($env, $delete_branch) {
    $workflow = $this->workflows->create(
      'delete_cloud_development_environment',
      array(
        'params' => array(
          'environment_id' => $env,
          'delete_branch'  => $delete_branch,
        )
      )
    );
    return $workflow;
  }

  /**
   * Deletes site from cache
   *
   * @return [void]
   */
  public function deleteFromCache() {
    $this->collection->deleteSiteFromCache($this->get('name'));
  }

  /**
   * Fetches this object from Pantheon
   *
   * @param [array] $options params to pass to url request
   * @return [Site] $this
   */
  public function fetch($options = array()) {
    $response         = TerminusCommand::simpleRequest(
      sprintf('sites/%s?site_state=true', $this->get('id'))
    );
    $this->attributes = $response['data'];
    return $this;
  }

  /**
   * Returns given attribute, if present
   *
   * @param [string] $attribute Name of attribute requested
   * @return [mixed] $this->attributes->$attributes;
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
   * @param [string] $feature Feature to check
   * @return [mixed] $this->features[$feature]
   */
  public function getFeature($feature) {
    if (!isset($this->features)) {
      $response       = TerminusCommand::simpleRequest(
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
   * @return [array] Array of SiteOrganizationMemberships
   */
  public function getOrganizations() {
    $orgs = $this->org_memberships->all();
    return $orgs;
  }

  /**
   * Lists user memberships for this site
   *
   * @return [SiteUserMemberships] Collection of user memberships for this site
   */
  public function getSiteUserMemberships() {
    $this->user_memberships = $this->user_memberships->fetch();
    return $this->user_memberships;
  }

  /**
   * Returns tags from the site/org join
   *
   * @param [string] $org UUID of organization site belongs to
   * @return [array] $tags Tags in string format
   */
  public function getTags($org) {
    if (isset($this->tags)) {
      return $this->tags;
    }
    $org_site_member = new OrganizationSiteMemberships(
      array('organization' => new Organization(null, array('id' => $org)))
    );
    $org_site_member->fetch();
    $org  = $org_site_member->get($this->get('id'));
    $tags = $org->get('tags');
    return $tags;
  }

  /**
   * Get upstream updates
   *
   * @return [stdClass] $response['data']
   */
  public function getUpstreamUpdates() {
    $response = TerminusCommand::simpleRequest(
      'sites/' . $this->get('id') .  '/code-upstream-updates'
    );
    return $response['data'];
  }

  /**
   * Imports a full-site archive
   *
   * @param [string] $url URL to import data from
   * @return [Workflow] $workflow
   */
  public function import($url) {
    $params = array(
      'url'      => $url,
      'code'     => 1,
      'database' => 1,
      'files'    => 1,
      'updatedb' => 1,
    );

    $workflow = $this->workflows->create(
      'do_import',
      array(
        'environment' => 'dev',
        'params'      => $params,
      )
    );
    return $workflow;
  }

  /**
   * Imports a database archive
   *
   * @param [string] $url URL to import data from
   * @return [Workflow] $workflow
   */
  public function importDatabase($url) {
    $workflow = $this->workflows->create(
      'import_database',
      array(
        'environment' => 'dev',
        'params'      => array('url' => $url),
      )
    );
    return $workflow;
  }

  /**
   * Imports a file archive
   *
   * @param [string] $url URL to import data from
   * @return [Workflow] $workflow
   */
  public function importFiles($url) {
    $workflow = $this->workflows->create(
      'import_files',
      array(
        'environment' => 'dev',
        'params'      => array('url' => $url),
      )
    );
    return $workflow;
  }

  /**
   * Load site info
   *
   * @param [string] $key Set to retrieve a specific attribute as named
   * @return [array] $info
   */
  public function info($key = null) {
    $info = array(
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
    );
    foreach ($info as $info_key => $datum) {
      if ($datum == null) {
        $info[$info_key] = $this->get($info_key);
      }
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
   * @return [stdClass] $response['data']
   */
  public function newRelic() {
    $path     = 'new-relic';
    $response = TerminusCommand::simpleRequest(
      'sites/' . $this->get('id') . '/new-relic'
    );
    return $response['data'];
  }

  /**
   * Returns all organization members of this site
   *
   * @param [string] $uuid UUID of organization to check for
   * @return [boolean] True if organization is a member of this site
   */
  public function organizationIsMember($uuid) {
    $org_ids       = $this->org_memberships->ids();
    $org_is_member = in_array($uuid, $org_ids);
    return $org_is_member;
  }

  /**
   * Removes payment instrument of given site
   *
   * @params [string] $uuid UUID of new payment instrument
   * @return [Workflow] $workflow Workflow object for the request
   */
  public function removeInstrument() {
    $args     = array('site'   => $this->get('id'),);
    $workflow = $this->workflows->create('disassociate_site_instrument', $args);
    return $workflow;
  }

  /**
   * Removes a tag to the site
   *
   * @param [string] $tag Tag to remove
   * @param [string] $org Organization to remove the tag associateion from
   * @return [array] $response
   */
  public function removeTag($tag, $org) {
    $response = TerminusCommand::simpleRequest(
      sprintf(
        'organizations/%s/tags/%s/sites?entity=%s',
        $org,
        $tag,
        $this->get('id')
      ),
      array('method' => 'delete')
    );
    return $response;
  }

  /**
   * Owner handler
   *
   * @param [string] $owner UUID of new owner of site
   * @return [stdClass] $data['data']
   */
  public function setOwner($owner = null) {
    $new_owner = $this->user_memberships->get($owner);
    if ($new_owner == null) {
      $message = 'The owner must be a team member. Add them with `site team`';
      throw new TerminusException($message);
    }
    $workflow = $this->workflows->create(
      'promote_site_user_to_owner',
      array(
        'params' => array(
          'user_id' => $new_owner->get('id')
        )
      )
    );
    return $workflow;
  }

  /**
   * Just the code branches
   *
   * @return [stdClass] $data['data']
   */
  public function tips() {
    $path = 'code-tips';
    $data = TerminusCommand::request('sites', $this->get('id'), $path, 'GET');
    return $data['data'];
  }

  /**
   * Update service level
   *
   * @param [string] $level Level to set service on site to
   * @return [stdClass] $response['data']
   */
  public function updateServiceLevel($level) {
    if (!in_array(
      $level,
      array('free', 'basic', 'pro', 'business', 'elite')
    )
    ) {
      throw new TerminusException(
        'Service level "{level}" is invalid.',
        compact('level'),
        1
      );
    }
    $path        = 'service-level';
    $method      = 'PUT';
    $form_params = $level;
    try {
      $response = TerminusCommand::request(
        'sites',
        $this->get('id'),
        $path,
        $method,
        compact('form_params')
      );
      return $response['data'];
    } catch (TerminusException $e) {
      if (strpos($e->getReplacements()['msg'], '403') !== false) {
        throw new TerminusException(
          'Instrument required to increase service level',
          array(),
          1
        );
      }
      throw $e;
    }
  }

  /**
   * Verifies if the given framework is in use
   *
   * @param [string] $framework_name Name of framework to verify
   * @return [boolean] $has_framework
   */
  private function hasFramework($framework_name) {
    $has_framework = ($framework_name == $this->get('framework'));
    return $has_framework;
  }

}
