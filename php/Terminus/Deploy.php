<?php
namespace Terminus;

use Terminus\Site;
use Terminus\Environment;

class Deploy {
  public $from;
  public $cc = false;
  public $annotation = "Deployed from terminus";
  public $deploy_code = true;
  public $updatedb = true;
  public $clone_files = true;
  public $clone_db = false;
  public $env;

  public function __construct(Environment $env, $params = array()) {
    if (!empty($params)) {
      foreach ($params as $key => $value) {
        if (property_exists($this,$key)) {
          $this->$key = $value;
        }
      }
    }
    $this->env = $env;
    return $this;
  }

  public function run() {
    $params = array(
      'type' => 'deploy',
      'params' => array(
        'annotation' => $this->annotation,
        'clear_cache' => $this->cc,
        'updatedb' => $this->updatedb,
      ),
    );
    $options = array( 'body' => json_encode($params) , 'headers'=>array('Content-type'=>'application/json') );
    $path = sprintf("environments/%s/workflows/deploy", $this->env->name);
    $response = \Terminus_Command::request( 'sites', $this->env->site->getId(), $path, 'POST', $options);
    return $response['data'];
  }
}
