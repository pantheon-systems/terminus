<?php

namespace Terminus;
use Terminus\Request;
use Terminus\Deploy;
use \Terminus_Command;
use Terminus\Collections\Environments;
use \Terminus\Environment;
use Terminus\Collections\Workflows;

class Site {
  public $id;
  public $attributes;
  public $information;
  public $metadata;
  public $environments = array();
  public $environmentsCollection;
  public $workflows;
  public $jobs;
  public $bindings;

  /**
  * Create a new Site
  * @param $options (array)
  *   @param $options label(string)
  *   @param $options name(string)
  *   @option $options organization_id(string)
  *   @option product_id(string)
  *
  * @return Workflow
  *
  */
  static function create($options = array()) {
    $data = array(
      'label' => $options['label'],
      'site_name' => $options['name']
    );

    if (isset($options['organization_id'])) {
      $data['organization_id'] = $options['organization_id'];
    }

    if (isset($options['product_id'])) {
      $data['deploy_product'] = array(
        'product_id' => $options['product_id']
      );
    }

    $user = User::instance();
    $workflow = $user->workflows->create('create_site', array(
      'params' => $data
    ));

    return $workflow;
  }

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

    if (property_exists($attributes, 'information')) {
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
    // cosmetic reasons for this
    $this->metadata = @$this->attributes->metadata ?: new \stdClass();
    # /deprecated properties

    $this->environmentsCollection = new Environments(array('site' => $this));
    $this->workflows = new Workflows(array('owner' => $this, 'owner_type' => 'site'));

    return $this;
  }

  public function fetch() {
    $response = Terminus_Command::simple_request(sprintf('sites/%s?site_state=true', $this->id));
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
    $atts = \Terminus_Command::request('sites',$this->getId(),$path,$method);
    return $atts['data'];
  }

  /**
   * Fetch Binding info
   */
  public function bindings($type=null) {
    if (empty($this->bindings)) {
      $path = "bindings";
      $response = \Terminus_Command::request('sites', $this->getId(), $path, "GET");
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
    $this->environmentsCollection->fetch();
    return $this->environmentsCollection->get($env_id);
  }

  /**
   * Load site info
   */
  public function info($key = null) {
    $dev_environment = $this->environmentsCollection->get('dev');
    
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
      'sftp_url' => $dev_environment ? $dev_environment->sftp_url() : '',
      'git_url' => $dev_environment ? $dev_environment->git_url() : '',
      'holder_type' => null,
      'holder_id' => null,
      'owner' => null,
    );
    foreach($info as $info_key => $datum) {
      if(($datum == null) && property_exists($this->information, $info_key)) {
        $info[$key] = $this->information->$info_key;
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
    $response = \Terminus_Command::request('sites', $this->getId(), $path, $method, $options);
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
     $response = \Terminus_Command::request('sites', $this->getId(), 'code-upstream', 'GET');
     return $response['data'];
   }

  /**
   * Get upstream updates
   */
   public function getUpstreamUpdates() {
     $response = \Terminus_Command::request('sites', $this->getId(), 'code-upstream-updates', 'GET');
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
    $response = \Terminus_Command::request('sites', $this->getId(), 'code-branch', 'POST', $options);
    return $response['data'];
  }

  /**
   * fetch jobs
  **/
  public function jobs() {
    if (!$this->jobs) {
      $response = \Terminus_Command::request('sites', $this->getId(), 'jobs', 'GET');
      $this->jobs = $response['data'];
    }
    return $this->jobs;
  }

  /**
   * Retrieve New Relic Info
   */
  public function newRelic() {
    $path = 'new-relic';
    $response = \Terminus_Command::request('sites', $this->getId(), 'new-relic', 'GET');
    return $response['data'];
  }

  /**
   * fetch notifications
  **/
  public function notifications() {
    $path = 'notifications';
    $data = \Terminus_Command::request('sites', $this->getId(), $path, 'GET');
    return $data['data'];
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
   * Delete a multidev environment
   */
  public function deleteEnvironment($env) {
    $workflow = $this->workflows->create('delete_cloud_development_environment', array(
      'params' => array(
        'environment_id' => $env
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
    $response = \Terminus_Command::request('sites', $site->getId(), $path, $method, $options);
    return $response['data'];
  }

  public function team() {
    $options = array();
    $response = \Terminus_Command::paged_request('sites/' . $this->getId() . '/memberships/users', $options);
    return $response['data'];
  }

  public function teamAddMember($email) {
    $method = 'POST';
    $path = sprintf('team/%s', urlencode($email));
    $data = array('invited_by' => Session::getValue('user_uuid'));
    $options = array( 'body' => json_encode($data) , 'headers'=>array('Content-type'=>'application/json'));
    $response = \Terminus_Command::request('sites', $this->getId(), $path, $method, $options);
    return $response['data'];
  }

  public function teamRemoveMember($uuid) {
    $method = 'DELETE';
    $path = sprintf('team/%s', $uuid);
    $response = \Terminus_Command::request('sites', $this->getId(), $path, $method);
    return $response['data'];
  }

  /**
  * Code branches
  */
  function tips() {
    $path = 'code-tips';
    $data = \Terminus_Command::request('sites',$this->getId(), $path, 'GET');
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
  function memberships($type='organizations') {
    $path = sprintf('memberships/%s', $type);
    $method = 'GET';
    $response = \Terminus_Command::request('sites', $this->getId(), $path, $method);
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

  public function get($attribute = 'id') {
    if(isset($this->attributes->$attribute)) {
      return $this->attributes->$attribute;
    }
    return null;
  }
}
