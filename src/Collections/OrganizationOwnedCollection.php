<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Friends\OrganizationInterface;
use Pantheon\Terminus\Friends\OrganizationTrait;

/**
 * Class OrganizationOwnedCollection
 * @package Pantheon\Terminus\Collections
 */
abstract class OrganizationOwnedCollection extends APICollection implements OrganizationInterface
{
    use OrganizationTrait;

    /**
     * @inheritdoc
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->setOrganization($options['organization']);
    }

    /**
     * @inheritdoc
     */
    public function getUrl()
    {
        // Replace the {organization_id} token with the actual organization id.
        return str_replace('{organization_id}', $this->getOrganization()->id, parent::getUrl());
    }
}
