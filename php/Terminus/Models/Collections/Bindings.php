<?php

namespace Terminus\Models\Collections;

use Terminus\Models\Binding;

class Bindings extends NewCollection {
  /**
   * @var Site
   */
  public $site;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\Binding';

  /**
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return Bindings
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->site = $options['site'];
    $this->url  = "sites/{$this->site->id}/bindings";
  }

  /**
   * Converges all bindings on a site
   *
   * @return array
   */
  public function converge() {
    $response = $this->request->request(
      "sites/{$this->site->id}/converge",
      ['method' => 'post']
    );
    return $response['data'];
  }

  /**
   * Get bindings by type
   *
   * @param string $type e.g. "appserver", "db server", etc
   * @return Binding[]
   */
  public function getByType($type) {
    $bindings = array_filter(
      $this->all(),
      function(Binding $binding) use ($type) {
        $is_valid = (
          $binding->get('type') == $type
          && !$binding->get('failover')
          && !$binding->get('slave_of')
        );
        return $is_valid;
      }
    );
    return $bindings;
  }

}
