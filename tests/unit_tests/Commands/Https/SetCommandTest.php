<?php


namespace Pantheon\Terminus\UnitTests\Https;

use Pantheon\Terminus\Commands\Https\SetCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

class SetCommandTest extends CommandTestCase
{
    public function testSetFiles()
    {
        $key = tempnam(sys_get_temp_dir(), 'terminus_key_');
        $cert = tempnam(sys_get_temp_dir(), 'terminus_cert_');
        $int = tempnam(sys_get_temp_dir(), 'terminus_int_');
        file_put_contents($key, '*KEY*');
        file_put_contents($cert, '*CERT*');
        file_put_contents($int, '*INT*');

        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->environment->expects($this->once())
            ->method('setHttpsCertificate')
            ->with(
                [
                    'cert' => '*CERT*',
                    'key' => '*KEY*',
                    'intermediary' => '*INT*',
                ]
            )
            ->willReturn($workflow);

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


        $command = new SetCommand();
        $command->setSites($this->sites);
        $command->setLogger($this->logger);
        $command->set(
            'mysite.dev',
            ['private-key' => $key, 'certificate' => $cert, 'intermediate-certificate' => $int]
        );

        unlink($key);
        unlink($cert);
        unlink($int);
    }

    public function testSetValues()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->environment->expects($this->once())
            ->method('setHttpsCertificate')
            ->with(
                [
                    'cert' => '*CERT*',
                    'key' => '*KEY*',
                    'intermediary' => '*INT*',
                ]
            )
            ->willReturn($workflow);

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

        $command = new SetCommand();
        $command->setSites($this->sites);
        $command->setLogger($this->logger);
        $command->set(
            'mysite.dev',
            ['private-key' => '*KEY*', 'certificate' => '*CERT*', 'intermediate-certificate' => '*INT*']
        );
    }
}
