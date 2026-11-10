<?php

namespace Pantheon\Terminus\PublicApi;

/**
 * Commands that talk to the Public API v1 implement this to receive the shared Client.
 *
 * This is the single container hook for the v1 stack (see Terminus::configureContainer()).
 * Implement setPublicApiClient() directly in the command; there is deliberately no trait.
 */
interface PublicApiAwareInterface
{
    public function setPublicApiClient(Client $client): void;
}
