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
  protected $backups;

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

  /**
   * @param $element sting code,file,backup
   */
  public function backups($element = null) {
    if (null === $this->backups ) {
      $path = sprintf("environments/%s/backups/catalog", $this->name);
      $response = \Terminus_Command::request('sites', $this->site, $path, 'GET');
      $this->backups = $response['data'];
    }
    $backups = (array) $this->backups;
    if ($element) {
      foreach ($this->backups as $id => $backup) {
        if (!isset($backup->filename)) {
          unset($backups[$id]);
          continue;
        }
        if (!preg_match("#.*$element\.\w+\.gz$#", $backup->filename)) {
          unset($backups[$id]);
          continue;
        }
      }
    }
    return $backups;
  }

  /**
   * @param $bucket string -- backup folder
   * @param $element string -- files,code,database
   */
  public function backupUrl($bucket, $element) {
    $path = sprintf("environments/%s/backups/catalog/%s/%s/s3token", $this->name, $bucket, $element);
    $data = array('method'=>'GET');
    $options = array('body'=>json_encode($data), 'headers'=> array('Content-type'=>'application/json') );
    $response = \Terminus_Command::request('sites', $this->site, $path, 'POST', $options);
    return $response['data'];
  }

  /**
   * Start a work flow
   * @param $workflow string work flow "slot"
   */
  public function workflow($workflow) {
    $path = sprintf("environments/%s/workflows", $this->name);
    $data = array(
      'type' => $workflow,
      'environment' => $this->name,
    );
    $options = array('body'=>json_encode($data), 'headers'=> array('Content-type'=>'application/json'));
    $response = \Terminus_Command::request('sites', $this->site, $path, 'POST', $options);

    return $response['data'];
  }

  /**
   * Get the code log
   */
  public function log() {
    $path = sprintf("environments/%s/code-log",$this->name);
    $response = \Terminus_Command::request('sites', $this->site, $path, 'GET');
    return $response['data'];
  }
}
