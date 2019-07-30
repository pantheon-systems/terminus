<?php

namespace Pantheon\Terminus\Helpers\AliasEmitters;

interface AliasEmitterInterface
{
    public function notificationMessage();
    public function write(AliasCollection $collection);
}
