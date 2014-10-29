<?php
namespace Terminus;
use \ReflectionClass;

abstract class Environment {
  protected $name = 'dev';
  protected $site = false;
  protected $diffstat;
  protected $dns_zone;
  protected $environment_created;
  protected $lock;
  protected $on_server_development;
  protected $randseed;
  protected $styx_cluster;
  protected $target_commit;
  protected $target_ref;
  protected $watchers;

  public function __construct($site, $environment = null ) {
    $this->site = $site;
    if (is_object($environment)) {
      // if we receive an environment object from the api hydrate the vars here
      // using the php reflection class pattern for fun here. perhaps there's a
      // better way.
      $environment_properties = get_object_vars($environment);
      // iterate our local properties setting them where available in the imported object
      foreach (get_object_vars($this) as $key => $value) {
        if(array_key_exists($key,$environment_properties)) {
          $this->$key = $environment_properties[$key];
        }
      }

    }

  }

  public function wipe() {
    return \Terminus_Command::request('sites', $this->site, "environments/{$this->name}/wipe", 'POST');
  }

  public function diffstat() {
    return $this->diffstat;
  }

}
