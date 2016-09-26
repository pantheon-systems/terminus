<?php

namespace Terminus\Models;

class Upstream extends TerminusModel
{
  /**
   * @var Site
   */
    public $site;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options with which to configure this model
   */
    public function __construct($attributes, array $options = [])
    {
        if (isset($attributes->product_id)) {
            $attributes->id = $attributes->product_id;
        }
        parent::__construct($attributes, $options);
        if (isset($options['site'])) {
            $this->site = $options['site'];
            $this->url  = "sites/{$this->site->id}?site_state=true";
        }
    }

  /**
   * Fetches this object from Pantheon
   *
   * @param array $args Params to pass to request
   * @return TerminusModel $this
   */
    public function fetch(array $args = [])
    {
        $options = array_merge(['options' => ['method' => 'get',],], $this->args, $args);
        $results = $this->request->request($this->url, $options);
        $this->attributes = $this->parseAttributes($results['data']->upstream);
        return $this;
    }

  /**
   * Returns the status of this site's upstream updates
   *
   * @return string $status 'outdated' or 'current'
   */
    public function getStatus()
    {
        if ($this->hasUpdates()) {
            $status = 'outdated';
        } else {
            $status = 'current';
        }
        return $status;
    }

  /**
   * Retrives upstream updates
   *
   * @return \stdClass
   */
    public function getUpdates()
    {
        $response = $this->request->request("sites/{$this->site->id}/code-upstream-updates");
        return $response['data'];
    }

  /**
   * Determines whether there are any updates to be applied.
   *
   * @return boolean
   */
    public function hasUpdates()
    {
        $updates = $this->getUpdates();
        $has_updates = ($updates->behind > 0);
        return $has_updates;
    }

  /**
   * Formats the Upstream object into an associative array for output
   *
   * @return array Associative array of data for output
   */
    public function serialize()
    {
        $data = [
        'url' => $this->get('url'),
        'product_id' => $this->get('product_id'),
        'branch' => $this->get('branch'),
        'status' => $this->getStatus(),
        ];
        return $data;
    }
}
