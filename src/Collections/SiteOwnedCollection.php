<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Friends\SiteInterface;
use Pantheon\Terminus\Friends\SiteTrait;

/**
 * Class SiteOwnedCollection
 * @package Pantheon\Terminus\Collections
 */
abstract class SiteOwnedCollection extends APICollection implements SiteInterface
{
    use SiteTrait;

    /**
     * @inheritdoc
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->setSite($options['site']);
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        return str_replace('{site_id}', $this->getSite()->id, parent::getUrl());
    }
}
