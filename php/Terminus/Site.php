<?php
namespace Terminus;
use Terminus\Request;
use Terminus\Deploy;
use \Terminus\SiteWorkflow;

class Site {
  public $id;
  public $information;
  public $metadata;
  public $environments = array();
  public $jobs;
  public $bindings;

  /**
   * Needs site object from the api to instantiate
   * @param $site (object) required - api site object
   */
  public function __construct($site) {
    $this->id = $site->id;
    $this->information = $site->information;
    // cosmetic reasons for this
    $this->information->id = $this->id;
    $this->metadata = @$site->metadata ?: new \stdClass();

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
   * Return all environments for a site
   */
  public function environments() {
    $cache = \Terminus::get_cache();
    if (empty($this->environments)) {
      if (!$environments = $cache->get_data("environments:{$this->id}")) {
        $results = \Terminus_Command::request("sites", $this->getId(), "environments", "GET");
        $environments = $results['data'];
        $cache->put_data("environments:{$this->id}",$environments);
      }
      $this->environments = $environments;
    }

    // instantiate local objects
    foreach ( $this->environments as $name => $env) {
      $this->environments->$name = EnvironmentFactory::load($this, $name, array(
        'hydrate_with' => $env,
      ));
    }

    return $this->environments;
  }

  /**
   * Return environment object from site
   * @param $environment string required
   */
  public function environment($environment) {
    if (array_key_exists($environment,$this->environments)) {
      return $this->environments->$environment;
    } else {
      // load the environments
      $this->environments();
    }
    return $this->environments->$environment;
  }

  /**
   * Returns the available environments
   */
  public function availableEnvironments() {
    $envs = array();
    if (empty($this->environments)) {
      $this->environments();
    }
    foreach ($this->environments as $name => $data) {
      $envs[] = $name;
    }

    # Reorder environments to put dev/test/live first
    $default_envs = array('dev', 'test', 'live');
    $multidev_envs = array_diff($envs, $default_envs);
    $envs = array_merge($default_envs, $multidev_envs);

    return $envs;
  }

  /**
   * Load site info
   */
  public function info($key = null) {
    $info = $this->information;
    if ($key AND property_exists($info, $key)) {
      return $info->$key;
    }
    return $this->information;
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
   */
   public function getName() {
     return $this->information->name;
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
   * @param $env string required -- environment name
   * @param $updatedb boolean (optional) -- whether to run update.php
   * @param $optionx boolean (optional) -- auto resolve merge conflicts
   * @todo This currently doesn't work and is block upstream
   */
  public function applyUpstreamUpdates($env, $updatedb = true, $xoption = 'theirs') {
    $data = array('updatedb' => $updatedb, 'xoption' => $xoption );
    $options = array( 'body' => json_encode($data) , 'headers'=>array('Content-type'=>'application/json') );
    $response = \Terminus_Command::request('sites', $this->getId(), 'code-upstream-updates', 'POST', $options);
    return $response['data'];
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
  public function import($url) {
    $path = 'environments/dev/import';
    $drush_archive = !(isset($this->information->framework) AND ($this->information->framework == 'wordpress'));
    $data = array('url'=>$url,'drush_archive' => (int)$drush_archive);
    $options = array( 'body' => json_encode($data) , 'headers'=>array('Content-type'=>'application/json') );
    $response = \Terminus_Command::request('sites', $this->getId(), $path, 'POST', $options);
    return $response['data'];
  }

  /**
   * get site instrument
   */
  public function instrument($org=null) {
    $path = 'instrument';
    $method = 'GET';
    $options = null;
    if ($org) {
      $path = 'workflows';
      $data = array(
          'type'    => 'transfer_site_payment_to_organization',
          'params'  => array(
              'to_organization_id' => $org,
          ),
        );
      $options = array( 'body' => json_encode($data) , 'headers'=>array('Content-type'=>'application/json') );
      $method = 'POST';
    }
    $response = \Terminus_Command::request('sites', $this->getId(), $path, $method, $options);
    return $response['data'];
  }

  /**
   * Create a multidev environment
   */
  public function createEnvironment($env, $src = 'dev') {
    $workflow = new SiteWorkflow('create_cloud_development_environment', $this);
    $workflow->setParams(array(
      'params' => array(
        'environment_id' => $env,
        'deploy' => array(
          'clone_database' => array( 'from_environment' => $src),
          'clone_files' => array( 'from_environment' => $src),
          'annotation' => sprintf("Create the '%s' environment.", $env)
        )
      )
    ));
    $workflow->start('POST');
    return $workflow;
  }

  /**
   * Delete a multidev environment
   */
  public function deleteEnvironment($env) {
    $workflow = new SiteWorkflow('delete_cloud_development_environment', $this);
    $workflow->setParams(array('params' => array('environment_id' => $env)));
    $workflow->start('POST');
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
    $method = 'GET';
    $path = 'team';
    $options = array();
    $response = \Terminus_Command::request('sites', $this->getId(), $path, $method, $options);
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
  public function addMembership($type,$name,$role='team_member') {
    $type = sprintf('add_site_%s_membership',$type);
    $workflow = new SiteWorkflow($type,$this);
    $workflow->setParams(array('params'=> array('organization_name'=>$name,'role'=>$role)));
    $workflow->start('POST');
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
    $workflow = new SiteWorkflow($type,$this);
    $workflow->setParams(array('params'=> array('organization_id'=>$uuid)));
    $workflow->start('POST');
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
}
