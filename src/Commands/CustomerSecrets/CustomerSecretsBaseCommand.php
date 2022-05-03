<?php

namespace Pantheon\Terminus\Commands\CustomerSecrets;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\SecretsApi\SecretsApiAwareTrait;
use Pantheon\Terminus\SecretsApi\SecretsApiAwareInterface;

/**
 * Class CustomerSecretsBaseCommand
 * Base class for Terminus commands that deal with customer secrets.
 *
 * @package Pantheon\Terminus\Commands\CustomerSecrets
 */
abstract class CustomerSecretsBaseCommand extends TerminusCommand implements SecretsApiAwareInterface
{
    use SecretsApiAwareTrait;
}
