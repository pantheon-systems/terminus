<?php

namespace Pantheon\Terminus\Helpers\AliasEmitters;

interface AliasEmitterInterface
{
    /**
     * Returns a string to use as a notificatin message for this emitter.
     *
     * @return string
     */
    public function notificationMessage();

    /**
     * Given an alias collection, write records for all aliases via this emitter.
     */
    public function write(AliasCollection $collection);
}
