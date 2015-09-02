<?php

namespace Terminus;

use stdClass;
use Terminus\Request;
use Terminus\Deploy;
use \TerminusCommand;
use \Terminus\Models\Environment;
use \Terminus\Models\SiteUserMembership;
use Terminus\Models\Organization;
use Terminus\Models\User;
use Terminus\Models\Collections\Environments;
use Terminus\Models\Collections\SiteUserMemberships;
use Terminus\Models\Collections\OrganizationSiteMemberships;
use Terminus\Models\Collections\SiteOrganizationMemberships;
use Terminus\Models\Collections\Workflows;

class Site {
  public $id;
  public $attributes;
  public $bindings;
  public $environments = array();
  public $environmentsCollection;
  public $information;
  public $metadata;
  public $workflows;

  private $features;
  private $org_memberships;
  private $tags;
  private $user_memberships;

  /**
   * Needs site object from the api to instantiate
   * @param $site (object) required - api site object
   */
  public function __construct($attributes) {
    if (is_string($attributes)) {
      $this->id = $attributes;
      $attributes = new \stdClass();
    } else {
      $this->id = $attributes->id;
    }

    if (is_object($attributes) && property_exists($attributes, 'information')) {
      # If the attributes has `information` property
      # unwrap it and massage the data into proper format
      $this->attributes = $attributes->information;
      $this->attributes->id = $attributes->id;
    } else {
      $this->attributes = $attributes;
    }

    # deprecated properties
    # this->information is deprecated, use $this->attributes
    $this->information = $this->attributes;
    $this->metadata = new \stdClass();
    if (isset($this->attributes->metadata)) {
      $this->metadata = $this->attributes->metadata;
    }

    $this->org_memberships = new SiteOrganizationMemberships(array('site' => $this));
    $this->user_memberships = new SiteUserMemberships(array('site' => $this));
    $this->environmentsCollection = new Environments(array('site' => $this));
    $this->workflows = new Workflows(array('owner' => $this));

    return $this;
  }

  /**
  * Create a new Site
  * @param $options (array)
  *   @param $options label(string)
  *   @param $options name(string)
  *   @option $options organization_id(string)
  *   @option upstream_id(string)
  *
  * @return Workflow
  *
  */
  static function create($options = array()) {
    $data = array(
      'label' => $options['label'],
      'site_name' => $options['name']
    );

    if(isset($options['organization_id'])) {
      $data['organization_id'] = $options['organization_id'];
    }

    if(isset($options['upstream_id'])) {
      $data['deploy_product'] = array(
        'product_id' => $options['upstream_id']
      );
    }

    $user = new User(new stdClass(), array());
    $workflow = $user->workflows->create('create_site', array(
      'params' => $data
    ));

    return $workflow;
  }

  public function fetch() {
    $response = TerminusCommand::simple_request(sprintf('sites/%s?site_state=true', $this->id));
    $this->attributes = $response['data'];
    # backwards compatibility
    $this->information = $this->attributes;

    return $this;
  }

  /**
   * returns array of attributes
   */
  public function attributes() {
    $path = "attributes";
    $method = "GET";
    $atts = \TerminusCommand::request('sites',$this->getId(),$path,$method);
    return $atts['data'];
  }

  /**
   * Fetch Binding info
   */
  public function bindings($type=null) {
    if (empty($this->bindings)) {
      $path = "bindings";
      $response = \TerminusCommand::request('sites', $this->getId(), $path, "GET");
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
   * Return a specifc environment on the site
   * @param $environment string required
   */
  public function environment($env_id) {
    return $this->environmentsCollection->get($env_id);
  }

  /**
   * Load site info
   */
  public function info($key = null) {
    $info = array(
      'id' => $this->id,
      'name' => null,
      'label' => null,
      'created' => null,
      'framework' => null,
      'organization' => null,
      'service_level' => null,
      'upstream' => null,
      'php_version' => null,
      'holder_type' => null,
      'holder_id' => null,
      'owner' => null,
    );
    foreach($info as $info_key => $datum) {
      if(($datum == null) && property_exists($this->information, $info_key)) {
        $info[$info_key] = $this->information->$info_key;
      }
    }

    if($key) {
      return isset($info[$key]) ? $info[$key] : null;
    } else {
      return $info;
    }
  }

  /**
   * Update service level
   */
  public function updateServiceLevel($level) {
    $path = "service-level";
    $method = 'PUT';
    $data = $level;
    $options = array( 'body' => json_encode($data) , 'headers'=>array('Content-type'=>'application/json') );
    $response = \TerminusCommand::request('sites', $this->getId(), $path, $method, $options);
    return $response['data'];
  }

  /**
   * Return site id
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Return site name
   *
   * @return [string] $this->information->name
   */
  public function getName() {
    if(property_exists($this->information, 'name')) {
      return $this->information->name;
    }
    return null;
  }

  /**
   * Get upstream info
   */
   public function getUpstream() {
     $response = \TerminusCommand::request('sites', $this->getId(), 'code-upstream', 'GET');
     return $response['data'];
   }

  /**
   * Get upstream updates
   */
   public function getUpstreamUpdates() {
     $response = \TerminusCommand::request('sites', $this->getId(), 'code-upstream-updates', 'GET');
     return $response['data'];
   }

  /**
   * Apply upstream updates
   * @param $environment_id string required -- environment name
   * @param $updatedb boolean (optional) -- whether to run update.php
   * @param $xoption boolean (optional) -- auto resolve merge conflicts
   */
  public function applyUpstreamUpdates($environment_id, $updatedb = true, $xoption = false) {
    $params = array(
      'updatedb' => $updatedb,
      'xoption'  => $xoption
    );

    $workflow = $this->workflows->create('apply_upstream_updates', array(
      'environment' => $environment_id,
      'params' => $params
    ));
    return $workflow;
  }

  /**
   * Create a new branch
   */
  public function createBranch($branch) {
    $data = array('refspec' => sprintf('refs/heads/%s', $branch));
    $options = array( 'body' => json_encode($data) , 'headers'=>array('Content-type'=>'application/json') );
    $response = \TerminusCommand::request('sites', $this->getId(), 'code-branch', 'POST', $options);
    return $response['data'];
  }

  /**
   * Retrieve New Relic Info
   */
  public function newRelic() {
    $path = 'new-relic';
    $response = \TerminusCommand::request('sites', $this->getId(), 'new-relic', 'GET');
    return $response['data'];
  }

  /**
   * Import Archive
   */
  public function import($url, $element) {
    $data = array(
      'url' => $url,
      'code' => 0,
      'database' => 0,
      'files' => 0,
      'updatedb' => 0
    );

    if($element == 'all') {
      $data = array_merge($data, array('code' => 1, 'database' => 1, 'files' => 1, 'updatedb' => 1));
    } elseif($element == 'database') {
      $data = array_merge($data, array('database' => 1, 'updatedb' => 1));
    } else {
      $data[$element] = 1;
    }

    $workflow = $this->workflows->create('do_import', array(
      'environment' => 'dev',
      'params' => $data
    ));
    return $workflow;
  }

  /**
   * Adds payment instrument of given site
   *
   * @params [string] $uuid UUID of new payment instrument
   * @return [Workflow] $workflow Workflow object for the request
   */
  public function addInstrument($uuid) {
    $args = array(
      'site'   => $this->id,
      'params' => array(
        'instrument_id' => $uuid
      )
    );
    $workflow = $this->workflows->create('associate_site_instrument', $args);
    return $workflow;
  }

  /**
   * Removes payment instrument of given site
   *
   * @params [string] $uuid UUID of new payment instrument
   * @return [Workflow] $workflow Workflow object for the request
   */
  public function removeInstrument() {
    $args = array(
      'site'   => $this->id,
    );
    $workflow = $this->workflows->create('disassociate_site_instrument', $args);
    return $workflow;
  }

  /**
   * Create a multidev environment
   */
  public function createEnvironment($env, $src = 'dev') {
    $workflow = $this->workflows->create('create_cloud_development_environment', array(
      'params' => array(
        'environment_id' => $env,
        'deploy' => array(
          'clone_database' => array( 'from_environment' => $src),
          'clone_files' => array( 'from_environment' => $src),
          'annotation' => sprintf("Create the '%s' environment.", $env)
        )
      )
    ));
    return $workflow;
  }

  /**
   * Delete a branch from site remove
   *
   * @param [string] $branch Name of branch to remove
   * @return [void]
   */
  public function deleteBranch($branch) {
    $workflow = $this->workflows->create('delete_environment_branch', array(
      'params' => array(
        'environment_id' => $branch,
      )
    ));
    return $workflow;
  }

  /**
   * Delete a multidev environment
   *
   * @param [string] $env            Name of environment to remove
   * @param [boolean] $delete_branch True to delete branch
   * @return [void]
   */
  public function deleteEnvironment($env, $delete_branch) {
    $workflow = $this->workflows->create('delete_cloud_development_environment', array(
      'params' => array(
        'environment_id' => $env,
        'delete_branch'  => $delete_branch,
      )
    ));
    return $workflow;
  }

  /**
   * Owner handler
   */
  public function owner($owner=null) {
    if ($owner !== null) {
      $method = 'PUT';
      $options = array( 'body' => json_encode($owner) , 'headers'=>array('Content-type'=>'application/json') );
    } else {
      $method = 'GET';
      $options = array();
    }
    $path = 'site-owner';
    $response = \TerminusCommand::request('sites', $site->getId(), $path, $method, $options);
    return $response['data'];
  }

  /**
  * Code branches
  */
  function tips() {
    $path = 'code-tips';
    $data = \TerminusCommand::request('sites',$this->getId(), $path, 'GET');
    return $data['data'];
  }

  /**
   * Add membshipship, either org or user
   *
   * @param $type string identifiying type of membership ... i.e. organization or user
   * @param $name string identifying the machine name of organization/user
   *
   * @return Workflow object
   **/
  public function addMembership($type, $name, $role = 'team_member') {
    $type = sprintf('add_site_%s_membership', $type);
    $workflow = $this->workflows->create($type, array(
      'params'=> array(
        'organization_name' => $name,
        'role' => $role
      )
    ));
    return $workflow;
  }

  /**
  * Remove membshipship, either org or user
  *
  * @param $type string identifiying type of membership ... i.e. organization or user
  * @param $uuid string identifying the machine name of organization/user
  *
  * @return Workflow object
  **/
  public function removeMembership($type,$uuid) {
    $type = sprintf('remove_site_%s_membership',$type);
    $workflow = $this->workflows->create($type, array(
      'params'=> array(
        'organization_id'=>$uuid
      )
    ));
    return $workflow;
  }

  /**
   * Get memberships for a site
  */
  public function memberships($type='organizations') {
    $path = sprintf('memberships/%s', $type);
    $method = 'GET';
    $response = \TerminusCommand::request('sites', $this->getId(), $path, $method);
    return $response['data'];
  }

  /**
  * Verifies if the given framework is in use
  *
  * @param $framework_name [String]
  *
  * @return [boolean]
  **/
  private function hasFramework($framework_name) {
    return isset($this->information->framework) && ($framework_name == $this->information->framework);
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
   * Returns a specific site feature value
   *
   * @param [string] $feature Feature to check
   * @return [mixed] $this->features[$feature]
   */
  public function getFeature($feature) {
    if(!isset($this->features)) {
      $response = TerminusCommand::request('sites', $this->id, 'features', 'GET');
      $this->features = (array)$response['data'];
    }
    if(isset($this->features[$feature])) {
      return $this->features[$feature];
    }
    return null;
  }

  /**
   * Adds a tag to the site
   *
   * @param [string] $tag Tag to apply
   * @return [array] $response
   */
  public function addTag($tag, $org) {
    $params   = array($tag => array('sites' => array($this->id)));
    $response = TerminusCommand::simple_request(
      sprintf('organizations/%s/tags', $org),
      array('method' => 'put', 'data' => $params)
    );
    return $response;
  }

  /**
   * Removes a tag to the site
   *
   * @param [string] $tag Tag to remove
   * @return [array] $response
   */
  public function removeTag($tag, $org) {
    $response = TerminusCommand::simple_request(
      sprintf('organizations/%s/tags/%s/sites?entity=%s', $org, $tag, $this->id),
      array('method' => 'delete')
    );
    return $response;
  }

  /**
   * Returns given attribute, if present
   *
   * @param [string] $attribute Name of attribute requested
   * @return [mixed] $this->attributes->$attributes;
   */
  public function get($attribute) {
    if(isset($this->attributes->$attribute)) {
      return $this->attributes->$attribute;
    }
    return null;
  }

  /**
   * Returns tags from the site/org join
   *
   * @param [string] $org  UUID of organization site belongs to
   * @return [array] $tags Tags in string format
   */
  public function getTags($org) {
    if (isset($this->tags)) {
      return $this->tags;
    }
    $org_site_member = new OrganizationSiteMemberships(
      array('organization' => new Organization(new stdClass(), array('id' => $org)))
    );
    $org_site_member->fetch();
    $org = $org_site_member->get($this->id);
    $tags = $org->get('tags');
    return $tags;
  }

  /**
   * Returns all organization members of this site
   *
   * @param [string] $uuid UUID of organization to check for
   * @return [boolean] True if organization is a member of this site
   */
  public function organizationIsMember($uuid) {
    $org_ids       = $this->org_memberships->fetch()->ids();
    $org_is_member = in_array($uuid, $org_ids);
    return $org_is_member;
  }

  /**
   * Returns all organization members of this site
   *
   * @return [array] Array of SiteOrganizationMemberships
   */
  public function getOrganizations() {
    $orgs = $this->org_memberships->fetch()->all();
    return $orgs;
  }

  /**
   * Retrieves a list of workflows run and running on this site
   *
   * @return [array] $workflows An array of Workflow objects
   */
  public function getWorkflows() {
    $workflows = $this->workflows->fetch($paged = true)->all();
    return $workflows;
  }
}
