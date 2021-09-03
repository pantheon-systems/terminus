<?php
declare(strict_types=1);

namespace Pantheon\Terminus;

use League\Container\Container;

// TODO: Move to Robo

class InflectionContainer extends Container
{
    public function inflect($obj)
    {
        return $this->inflectors->inflect($obj);
    }
}
