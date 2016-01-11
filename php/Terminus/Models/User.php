<?php

namespace Terminus\Models;

use Terminus\Models\Collections\UserOrganizationMemberships;
use Terminus\Models\Collections\Instruments;
use Terminus\Models\Collections\Sites;
use Terminus\Models\Collections\Workflows;
use Terminus\Session;

class User extends TerminusModel {
  /**
   * @var UserOrganizationMemberships
   */
  public $organizations;

  /**
   * @var Instruments
   */
  protected $instruments;

  /**
   * @var Workflows
   */
  protected $workflows;

  /**
   * @var \stdClass
   * @todo Wrap this in a proper class.
   */
  private $aliases;

  /**
   * @var \stdClass
   * @todo Wrap this in a proper class.
   */
  private $profile;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   */
  public function __construct($attributes = null, array $options = array()) {
    if (!isset($options['id'])) {
      $options['id'] = Session::getValue('user_uuid');
    }
    parent::__construct($attributes, $options);

    if (isset($attributes->profile)) {
      $this->profile = $attributes->profile;
    }
    $this->workflows     = new Workflows(
      array('owner' => $this)
    );
    $this->instruments   = new Instruments(array('user' => $this));
    $this->organizations = new UserOrganizationMemberships(
      array('user' => $this)
    );
  }

  /**
   * Retrieves drush aliases for this user
   *
   * @return \stdClass
   */
  public function getAliases() {
    if (!$this->aliases) {
      $this->setAliases();
    }
    return $this->aliases;
  }

  /**
   * Retrieves organization data for this user
   *
   * @return Organization[]
   */
  public function getOrganizations() {
    $organizations = $this->organizations->all();
    return $organizations;
  }

  /**
   * Requests API data and populates $this->aliases
   *
   * @return void
   */
  private function setAliases() {
    $path     = 'drush_aliases';
    $method   = 'GET';
    $response = $this->request->request('users', $this->id, $path, $method);
    eval(str_replace('<?php', '', $response['data']->drush_aliases));

    $formatted_aliases = substr($response['data']->drush_aliases, 0, -1);

    $sites_object = new Sites();
    $sites        = $sites_object->all();
    foreach ($sites as $site) {
      $environments = $site->environments->all();
      foreach ($environments as $environment) {
        $key = $site->get('name') . '.'. $environment->get('id');
        if (isset($aliases[$key])) {
          break;
        }
        try {
          $formatted_aliases .= PHP_EOL . "  \$aliases['$key'] = ";
          $formatted_aliases .= $this->constructAlias($environment);
        } catch (TerminusException $e) {
          continue;
        }
      }
    }
    $formatted_aliases .= PHP_EOL;
    $this->aliases      = $formatted_aliases;
  }

  /**
   * Constructs a Drush alias for an environment. Used to supply
   *   organizational Drush aliases not provided by the API.
   *
   * @param Environment $environment Environment to create an alias for
   * @return string
   */
  private function constructAlias($environment) {
    $site_name   = $environment->site->get('name');
    $site_id     = $environment->site->get('id');
    $env_id      = $environment->get('id');
    $db_bindings = $environment->bindings->getByType('dbserver');
    $hostnames   = array_keys((array)$environment->getHostnames());
    if (empty($hostnames) || empty($db_bindings)) {
      throw new TerminusException(
        'No hostname entry for {site}.{env}',
        ['site' => $site_name, 'env' => $env_id,],
        1
      );
    }
    $db_binding = array_shift($db_bindings);
    $uri        = array_shift($hostnames);
    $db_pass    = $db_binding->get('password');
    $db_port    = $db_binding->get('port');

    if (strpos(TERMINUS_HOST, 'onebox') !== false) {
      $remote_user = "appserver.$env_id.$site_id";
      $remote_host = TERMINUS_HOST;
      $db_url      = "mysql://pantheon:$db_pass@$remote_host:$db_port";
      $db_url     .= '/pantheon';
    } else {
      $remote_user = "$env_id.$site_id";
      $remote_host = "appserver.$env_id.$site_id.drush.in";
      $db_url      = "mysql://pantheon:$db_pass@dbserver.$environment.$site_id";
      $db_url     .= ".drush.in:$db_port/pantheon";
    }
    $output = "array(
    'uri'              => $uri,
    'db-url'           => $db_url,
    'db-allows-remote' => true,
    'remote-host'      => $remote_host,
    'remote-user'      => $remote_user,
    'ssh-options'      => '-p 2222 -o \"AddressFamily inet\"',
    'path-aliases'     => array(
      '%files'        => 'code/sites/default/files',
      '%drush-script' => 'drush',
    ),
  );";
    return $output;
  }

}
