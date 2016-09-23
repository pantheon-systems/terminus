<?php

namespace Terminus\Collections;

use Terminus\Models\Binding;

class Bindings extends TerminusCollection
{
  /**
   * @var Environment
   */
    public $environment;
  /**
   * @var string
   */
    protected $collected_class = 'Terminus\Models\Binding';

  /**
   * Object constructor
   *
   * @param array $options Options to set as $this->key
   */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->environment = $options['environment'];
        $this->url = "sites/{$this->environment->site->id}/bindings";
    }

  /**
   * Get bindings by type
   *
   * @param string $type e.g. "appserver", "db server", etc
   * @return Binding[]
   */
    public function getByType($type)
    {
        $models = array_filter(
            $this->all(),
            function (Binding $binding) use ($type) {
                $is_valid = (
                $binding->get('type') == $type
                && !$binding->get('failover')
                && !$binding->get('slave_of')
                );
                return $is_valid;
            }
        );

        $bindings = array_values($models);
        return $bindings;
    }
}
