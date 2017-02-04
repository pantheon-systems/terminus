<?php

namespace Pantheon\Terminus\UnitTests\Config;

use Pantheon\Terminus\Config\DefaultsConfig;

class DummyConfigClass extends DefaultsConfig
{
    /**
     * Exposes the getTerminusRoot function for testing purposes
     *
     * @param string $dir
     * @return string
     */
    public function runGetTerminusRoot($dir = null)
    {
        return $this->getTerminusRoot($dir);
    }
}
