<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Binding;
use Pantheon\Terminus\Models\Environment;

/**
 * Class Bindings
 * @package Pantheon\Terminus\Collections
 */
class Bindings extends EnvironmentOwnedCollection
{
    const PRETTY_NAME = 'bindings';
    /**
     * @var string
     */
    protected $collected_class = Binding::class;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/bindings';

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
