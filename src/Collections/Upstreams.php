<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Upstream;

/**
 * Class Upstreams
 * @package Pantheon\Terminus\Collections
 */
class Upstreams extends UserOwnedCollection
{
    public static $pretty_name = 'upstreams';
    /**
     * @var string
     */
    protected $collected_class = Upstream::class;
    /**
     * @var string
     */
    protected $url = 'products';
}
