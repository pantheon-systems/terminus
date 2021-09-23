<?php

namespace Pantheon\Terminus\UnitTests\Site;

use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * @todo: implement tests for SiteAwareTrait.
 */
class SiteAwareTraitTest extends CommandTestCase
{
    /**
     * @var DummyClass
     */
    protected $class;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->class = new DummyClass();
        $this->class->setSites($this->sites);
    }
}
