<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Friends\SiteInterface;
use Pantheon\Terminus\Friends\SiteTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class AddOnModel
 * @package Pantheon\Terminus\Models
 */
abstract class AddOnModel extends TerminusModel implements ConfigAwareInterface, SiteInterface
{
    use ConfigAwareTrait;
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
