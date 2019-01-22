<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Friends\SiteInterface;
use Pantheon\Terminus\Friends\SiteTrait;

/**
 * Class AddOnModel
 * @package Pantheon\Terminus\Models
 */
abstract class AddOnModel extends TerminusModel implements SiteInterface
{
    use SiteTrait;

    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->setSite($options['site']);
    }
}
