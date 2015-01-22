<?php
namespace Terminus;

class SiteWorkflow {
  public $result;
  public $id;
  public $realm;
  public $type;
  public $params = array();
  public $site;
  public $status;

  public function __construct($type, Site $site) {
    $this->type = $type;
    $this->setParams(array('type'=> $type));
    $this->site = $site;
  }

  /**
   * Factory method
   */
  public static function createWorkflow($type, Site $site) {
    return new SiteWorkflow($type, $site);
  }

  /**
   * Save an associative array into the params array
   * @param $args array required
   *
   * @return workflow object
   */
  public function setParams($args) {
    foreach($args as $key => $value) {
      $this->params[$key] = $value;
    }
    return $this;
  }

  /**
   * Send the workflow to the api
   */
  public function start($method='GET') {
    $data = array();
    $path = 'workflows';
    if ('POST' == $method) {
      $data['body'] = json_encode($this->params);
      $data['headers'] = array('Content-type'=>'application/json');
    } else {
      $path = "$path?type=".$this->type;
    }
    $response = \Terminus_Command::request('sites', $this->site->getId(), $path, $method, $data);

    if (is_object($response['data'])) {
      $this->status = $response['data'];
      $this->id = $this->status->id;
      $this->result = $this->status->result;
    }
    return $this;
  }

  public function refresh() {
    $response = self::request('sites', $this->site->getId(), "workflows/".$this->id(), 'GET');
    print_r($response);
    $this->status = $response['data'];
    $this->id = $this->status->id;
    $this->result = $this->status->result;
  }

  /**
   * Wait on workflow to complete
   */
  public function wait() {
    \Terminus::set_config('nocache',true);
    $tries = 0;
    while( $this->status('result') !== 'succeeded' AND $tries < 100) {
      if ( 'failed' == $this->status('result') OR 'aborted' == $this->status('result') ) {
        if (isset($this->status->final_task) and !empty($this->status->final_task->messages)) {
          foreach($this->status->final_task->messages as $data => $message) {
            \Terminus::error(sprintf('[%s] %s', $message->level, $message->message));
            exit;
          }
        } else {
          \Terminus::error(PHP_EOL."Couldn't complete jobs: '{$this->type}'".PHP_EOL);
        }
      }
      sleep(3);
      print ".";
      $tries++;
    }
    print PHP_EOL;
    if( "succeeded" === $this->status('result') )
      return $this;
    return false;
    unset($workflow);
  }

  /**
   * Return current status
   * @param $key string optional -- property to return
   */
   public function status($key=null) {
     if ($key AND is_object($this->status) AND property_exists($this->status,$key)) {
       return $this->status->$key;
     } elseif ($key AND is_object($this->status) AND !property_exists($this->status,$key)) {
       return false;
     }
     return $this->status;
   }

}
