<?php

namespace Pantheon\Terminus\UnitTests\Friends\Environment;

use Pantheon\Terminus\Models\Environment;

/**
 * Class SingularTest
 * Testing class for Pantheon\Terminus\Friends\EnvironmentTrait & Pantheon\Terminus\Friends\EnvironmentInterface
 * @package Pantheon\Terminus\UnitTests\Friends\Environment
 */
class SingularTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SingularDummyClass
     */
    protected $collection;
    /**
     * @var SingularDummyClass
     */
    protected $model;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->getMockBuilder(SingularDummyClass::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = new SingularDummyClass(null, ['collection' => $this->collection,]);
    }

    /**
     * Tests EnvironmentTrait::getEnvironment()
     */
    public function testGetEnvironment()
    {
        $environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->expects($this->once())
            ->method('getEnvironment')
            ->with()
            ->willReturn($environment);

        $this->assertEquals($environment, $this->model->getEnvironment());
    }

    /**
     * Tests EnvironmentTrait::setEnvironment()
     */
    public function testSetEnvironment()
    {
        $environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collection->expects($this->never())->method('getEnvironment');

        $this->model->setEnvironment($environment);
        $this->assertEquals($environment, $this->model->getEnvironment());
    }
}
