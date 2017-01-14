<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Pantheon\Terminus\Commands\Site\DeleteCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Symfony\Component\Console\Input\Input;

/**
 * Class DeleteCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Site\DeleteCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site
 */
class DeleteCommandTest extends CommandTestCase
{

    /**
     * @inheritdoc
     */
    protected function setup()
    {
        parent::setUp();

        $this->command = new DeleteCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);
    }

    /**
     * Exercises the site:delete command
     */
    public function testDelete()
    {
        $site_name = 'my-site';

        $this->site->expects($this->once())
            ->method('delete')
            ->with();
        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Deleted {site} from Pantheon'),
                $this->equalTo(['site' => $site_name,])
            );

        $out = $this->command->delete($site_name);
        $this->assertNull($out);
    }


    /**
     * Exercises the site:delete command when Site::delete fails to ensure message gets through
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Error message
     */
    public function testDeleteFailure()
    {
        $site_name = 'my-site';

        $this->site->expects($this->once())
            ->method('delete')
            ->with()
            ->will($this->throwException(new \Exception('Error message')));
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->delete($site_name);
        $this->assertNull($out);
    }
}
