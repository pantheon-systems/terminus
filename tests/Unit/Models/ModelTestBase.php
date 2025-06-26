<?php

namespace Pantheon\Terminus\Tests\Unit\Models;

use Pantheon\Terminus\Collections\TerminusCollection;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Models\TerminusModel;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Tests\Unit\TerminusTestCase;

/**
 * Class ModelTestCase
 * @package Pantheon\Terminus\UnitTests\Models
 */
abstract class ModelTestBase extends TerminusTestCase
{
    protected \ReflectionClass $reflector;

    public function setUp(): void
    {
        parent::setUp();

        $this->reflector = new \ReflectionClass($this->getClass());
    }

    /**
     * @return void
     * @test
     * @dataProvider dataProvider
     * @throws \ReflectionException
     */
    abstract public function testModel(array $data);

    /**
     * @return array
     */
    abstract public function dataProvider(): array;

    /**
     * @return string
     */
    abstract protected function getClass(): string;
}
