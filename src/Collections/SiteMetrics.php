<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Metric;
use Pantheon\Terminus\Site\SiteMetricsTrait;

/**
 * Class SiteMetrics
 * @package Pantheon\Terminus\Collections
 */
class SiteMetrics extends SiteOwnedCollection
{
    const PRETTY_NAME = 'metrics';

    use SiteMetricsTrait;

    /**
     * @var string
     */
    protected $collected_class = Metric::class;

    /**
     * @var string base URL to fetch
     */
    protected $url = 'sites/{site_id}/{series}?granularity={period}&datapoints={datapoints}';

    /**
     * SiteMetrics constructor
     */
    public function __constructor()
    {
    }
}
