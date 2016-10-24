<?php


namespace Pantheon\Terminus\UnitTests\Https;

use Pantheon\Terminus\Commands\Https\DeleteCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Exceptions\TerminusException;

class DeleteCommandTest extends CommandTestCase
{
    public function testDelete()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->environment->expects($this->once())
            ->method('disableHttpsCertificate');
        $this->environment->expects($this->once())
            ->method('convergeBindings')
            ->willReturn($workflow);


        // should display a notice about the mode switch
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('HTTPS has been disabled and the environment\'s bindings will now be converged.')
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );


        $command = new DeleteCommand();
        $command->setSites($this->sites);
        $command->setLogger($this->logger);
        $command->delete('mysite.dev');
    }

    public function testDeleteFailed()
    {
        $this->environment->expects($this->once())
            ->method('disableHttpsCertificate')
            ->will($this->throwException(new TerminusException('Could not delete')));

        $command = new DeleteCommand();
        $command->setSites($this->sites);

        $this->setExpectedException(TerminusException::class);
        $command->delete('mysite.dev');
    }
}
