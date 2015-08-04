<?php
namespace Terminus\Models;
use \Terminus\Request;
use \Terminus_Command;

class Workflow {
  public $id;
  public $attributes;
  public $collection;

  public $owner;
  public $owner_type;

  public function __construct($attributes, $options = array()) {
    $this->id = $attributes->id;
    $this->attributes = $attributes;

    if (isset($options['collection'])) {
      $this->collection = $options['collection'];
    }

    $this->owner = $options['owner'];
    $this->owner_type = $options['owner_type'];

    return $this;
  }

  public function url() {
    switch ($this->owner_type) {
      case 'user':
        return sprintf("users/%s/workflows/%s", $this->owner->id, $this->id);
      case 'site':
        return sprintf("sites/%s/workflows/%s", $this->owner->id, $this->id);
      case 'organization':
        return sprintf("users/%s/organizations/%s/workflows/%s", $this->owner->user->id, $this->owner->id, $this->id);
    }
  }

  public function fetch() {
    $results = Terminus_Command::simple_request($this->url());
    $this->attributes = $results['data'];
    return $this;
  }

  public function wait() {
    while (!$this->isFinished()) {
      $this->fetch();
      sleep(3);
      print ".";
    }
    if ($this->isSuccessful()) {
      return $this;
    } else {
      if (isset($this->attributes->final_task) and !empty($this->attributes->final_task->messages)) {
        foreach($this->attributes->final_task->messages as $data => $message) {
          \Terminus::error(sprintf('[%s] %s', $message->level, $message->message));
          exit;
        }
      }
    }
  }

   public function isFinished() {
     return (boolean)$this->attributes->result;
   }

   public function isSuccessful() {
     return $this->attributes->result == 'succeeded';
   }

   public function logMessages() {
     foreach($this->attributes->final_task->messages as $data => $message) {
       \Terminus::error(sprintf('[%s] %s', $message->level, $message->message));
     }
   }
}
