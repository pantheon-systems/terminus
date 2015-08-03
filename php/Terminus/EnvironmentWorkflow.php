<?php
namespace Terminus;

use \Terminus\Workflow;

class EnvironmentWorkflow extends Workflow {
  
  public function refresh() {
    $path = sprintf('environments/%s/workflows', $this->object->name);
    $response = \Terminus_Command::request($this->realm, $this->object->site->getId(), sprintf("workflows/%s",$this->id), 'GET');
    $this->status = $response['data'];
    $this->id = $response['data']->id;
    $this->result = $this->status->result;
  }

  /**
   * Send the workflow to the api
   */
  public function start() {
    $data = array();
    $path = sprintf('environments/%s/workflows', $this->object->name);
    if ('POST' == $this->getMethod()) {
      $data['body'] = array('type' => $this->type);
      if(isset($this->params)) {
        $data['body']['params'] = $this->params;
      }
      $data['body'] = json_encode($data['body']);
      $data['headers'] = array('Content-type' => 'application/json');
    } else {
      $path = "$path?type=".$this->type;
    }

    $response = \Terminus_Command::request($this->realm, $this->object->site->getId(), $path, $this->getMethod(), $data);

    if (is_object($response['data'])) {
      $this->status = $response['data'];
      $this->id = $this->status->id;
      $this->result = $this->status->result;
    }
    return $this;
  }

}
