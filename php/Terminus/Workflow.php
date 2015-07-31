<?php
namespace Terminus;

class Workflow {
  protected $result;
  protected $id;
  protected $realm;
  protected $type;
  protected $params = array();
  protected $object;
  protected $status;
  protected $user;
  protected $method;

  public function __construct($type, $realm, $object) {
    $this->type = $type;
    $this->realm = $realm;
    $this->object = $object;
  }

  /**
   * Factory method
   */
  public static function createWorkflow($type, $realm, $object) {
    return new self($type, $realm, $object);
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

  public function setMethod($method) {
    $this->method = $method;
  }

  public function getMethod() {
    return $this->method;
  }

  /**
   * Send the workflow to the api
   */
  public function start() {
    $data = array();
    $path = 'workflows';
    if ('POST' == $this->getMethod()) {
      $data['body'] = json_encode(
        array(
          'type' => $this->type,
          'params' => $this->params
        )
      );
      $data['headers'] = array('Content-type'=>'application/json');
    } else {
      $path = "$path?type=".$this->type;
    }

    $response = \Terminus_Command::request($this->realm, $this->object->getId(), $path, $this->getMethod(), $data);

    if (is_object($response['data'])) {
      $this->status = $response['data'];
      $this->id = $this->status->id;
      $this->result = $this->status->result;
    }
    return $this;
  }

  public function refresh() {
    $response = \Terminus_Command::request($this->realm, $this->object->getId(), "workflows/".$this->id, 'GET');
    $this->status = $response['data'];
    $this->id = $response['data']->id;
    $this->result = $this->status->result;
  }

  /**
   * Wait on workflow to complete
   */
  public function wait() {
    $tries = 0;
    while( $this->status('result') !== 'succeeded' AND $tries < 100) {
      if ( 'failed' == $this->status('result') OR 'aborted' == $this->status('result') ) {
        if (isset($this->status->final_task) and !empty($this->status->final_task->messages)) {
          foreach($this->status->final_task->messages as $data => $message) {
            \Terminus::error(sprintf('[%s] %s', $message->level, $message->message));
            exit;
          }
        } else {
          \Terminus::error(PHP_EOL."Couldn't complete workflow: '{$this->type}'".PHP_EOL);
        }
      }
      sleep(3);
      $this->refresh();
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
     if ($key AND is_object($this->status) AND property_exists($this->status, $key)) {
       return $this->status->$key;
     } elseif ($key AND is_object($this->status) AND !property_exists($this->status,$key)) {
       return false;
     }
     return $this->status;
   }

   public function isFinished() {
     return (boolean)$this->status->result;
   }

   public function isSuccessful() {
     return $this->status->result == 'succeeded';
   }

   public function logMessages() {
     foreach($this->status->final_task->messages as $data => $message) {
       \Terminus::error(sprintf('[%s] %s', $message->level, $message->message));
     }
   }
}
