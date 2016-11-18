<?php

namespace Pantheon\Terminus\UnitTests\HTTPS;

use Pantheon\Terminus\Commands\HTTPS\SetCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class SetCommandTest
 * Test suite for class for Pantheon\Terminus\Commands\HTTPS\SetCommand
 * @package Pantheon\Terminus\UnitTests\HTTPS
 */
class SetCommandTest extends CommandTestCase
{
    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn('successful workflow');

        $this->environment->expects($this->once())
            ->method('setHttpsCertificate')
            ->with(
                [
                    'cert' => '*CERT*',
                    'key' => '*KEY*',
                    'intermediary' => '*INT*',
                ]
            )
            ->willReturn($this->workflow);

        // should display a notice about the mode switch
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('SSL certificate updated. Converging loadbalancer.')
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );


        $this->command = new SetCommand();
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests connection:set while setting with files
     */
    public function testSetFiles()
    {
        $key = tempnam(sys_get_temp_dir(), 'terminus_key_');
        $cert = tempnam(sys_get_temp_dir(), 'terminus_cert_');
        $int = tempnam(sys_get_temp_dir(), 'terminus_int_');
        file_put_contents($key, '*KEY*');
        file_put_contents($cert, '*CERT*');
        file_put_contents($int, '*INT*');

        $this->command->set('mysite.dev', $cert, $key, ['intermediate-certificate' => $int,]);

        unlink($key);
        unlink($cert);
        unlink($int);
    }

    /**
     * Tests connection:set while setting with values
     */
    public function testSetValues()
    {
        $this->command->set('mysite.dev', '*CERT*', '*KEY*', ['intermediate-certificate' => '*INT*',]);
    }
}
