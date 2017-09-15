<?php

namespace Pantheon\Terminus\UnitTests\Site;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

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

    public function testGetSiteEnv()
    {
        $this->site->id = 'site id';
        $this->environment->id = 'environment id';

        list($site, $env) = $this->class->getSiteEnv("{$this->site->id}.{$this->environment->id}");

        $this->assertInstanceOf(Site::class, $site);
        $this->assertInstanceOf(Environment::class, $env);
    }

    /**
     * Tests SiteAwareTrait::getSiteEnv(string) when the given string is in an incorrect format
     */
    public function testGetSiteEnvWrongFormat()
    {
        $this->setExpectedException(
            TerminusException::class,
            'The environment argument must be given as <site_name>.<environment>'
        );

        $out = $this->class->getSiteEnv('');
        $this->assertNull($out);
    }

    public function testGetUnfrozenSiteEnv()
    {
        $this->site->id = 'site id';
        $this->environment->id = 'live';

        $this->site->expects($this->once())
            ->method('isFrozen')
            ->willReturn(false);

        list($site, $env) = $this->class->getUnfrozenSiteEnv("{$this->site->id}.{$this->environment->id}");

        $this->assertInstanceOf(Site::class, $site);
        $this->assertInstanceOf(Environment::class, $env);
    }

    /**
     * Tests SiteAwareTrait::getUnfrozenSiteEnv(string) when the site is frozen and the env test or live
     */
    public function testGetUnfrozenSiteEnvFrozenAndLive()
    {
        $this->site->id = 'site id';
        $this->environment->id = 'live';

        $this->site->expects($this->once())
            ->method('isFrozen')
            ->willReturn(true);

        $this->setExpectedException(
            TerminusException::class,
            'This site is frozen. Its test and live environments and many commands will be unavailable while it remains frozen.'
        );

        $out = $this->class->getUnfrozenSiteEnv("{$this->site->id}.{$this->environment->id}");
        $this->assertNull($out);
    }
}
