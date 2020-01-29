<?php

namespace Pantheon\TerminusHello\Hooks;

use Symfony\Component\Console\Input\InputInterface;

class InitHook
{
    /**
     * @hook init *
     * @option boolean $new I wasn't here before
     */
    public function initHook(InputInterface $input)
    {
        // Don't fail for not being here
    }
}
