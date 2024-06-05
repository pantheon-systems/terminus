<?php

namespace Pantheon\Terminus\Tests\Unit\Collections;

use PHPUnit\Framework\TestCase;

abstract class CollectionTestBase extends TestCase
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
    abstract public function testCollection(array $data);

    /**
     * @return array
     */
    abstract public function dataProvider();

    /**
     * @return string
     */
    abstract protected function getClass(): string;
}
