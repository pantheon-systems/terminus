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
     *
     * @param array $alias_replacements Associative array of site id => alias replacement data
     */
    public function write(array $alias_replacements);
}
