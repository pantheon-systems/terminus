<?php

namespace Pantheon\Terminus\Commands\Org;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Organization\OrganizationAwareInterface;
use Pantheon\Terminus\Organization\OrganizationAwareTrait;

/**
 * Class OrgCommand
 */
abstract class OrgCommand extends TerminusCommand implements OrganizationAwareInterface
{

    use OrganizationAwareTrait;
}
