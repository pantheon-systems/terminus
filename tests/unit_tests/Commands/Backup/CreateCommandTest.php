<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Pantheon\Terminus\Commands\Backup\CreateCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class CreateCommandTest
 * Testing class for Pantheon\Terminus\Commands\Backup\CreateCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Backup
 */
class CreateCommandTest extends BackupCommandTest
{
    use WorkflowProgressTrait;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new CreateCommand($this->sites);
        $this->command->setContainer($this->getContainer());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->expectWorkflowProcessing();
    }

    /**
     * Tests the backup:create command without any options
     */
    public function testCreateBackup()
    {
        $this->environment->id = 'env_id';

        $this->backups->expects($this->once())
            ->method('create')
            ->with($this->equalTo(['element' => null, 'keep-for' => 365,]))
            ->willReturn($this->workflow);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Created a backup of the {env} environment.'),
                $this->equalTo(['env' => $this->environment->id,])
            );

        $out = $this->command->create("mysite.{$this->environment->id}");
        $this->assertNull($out);
    }

    /**
     * Tests the backup:create command with a set number of days to keep the backup for
     */
    public function testCreateBackupWithKeepFor()
    {
        $this->environment->id = 'env_id';
        $params = ['keep-for' => 55,];

        $this->backups->expects($this->once())
            ->method('create')
            ->with($this->equalTo($params + ['element' => null,]))
            ->willReturn($this->workflow);

        $this->logger->expects($this->once())
          ->method('log')
          ->with(
              $this->equalTo('notice'),
              $this->equalTo('Created a backup of the {env} environment.'),
              $this->equalTo(['env' => $this->environment->id,])
          );

        $out = $this->command->create("mysite.{$this->environment->id}", $params);
        $this->assertNull($out);
    }

    /**
     * Tests the backup:create command when creating a backup for a specific element
     */
    public function testCreateBackupElement()
    {
        $this->environment->id = 'env_id';
        $params = ['element' => 'db',];

        $this->backups->expects($this->once())
            ->method('create')
            ->with($this->equalTo(['element' => 'database',]))
            ->willReturn($this->workflow);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Created a backup of the {env} environment.'),
                $this->equalTo(['env' => $this->environment->id,])
            );

        $out = $this->command->create("mysite.{$this->environment->id}", $params);
        $this->assertNull($out);
    }

    /**
     * Tests the backup:create command when creating a backup for a specific element and a set number of days to keep
     */
    public function testCreateBackupElementWithKeepFor()
    {
        $this->environment->id = 'env_id';
        $params = ['element' => 'db', 'keep-for' => 89,];

        $this->backups->expects($this->once())
            ->method('create')
            ->with($this->equalTo(['element' => 'database', 'keep-for' => 89,]))
            ->willReturn($this->workflow);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Created a backup of the {env} environment.'),
                $this->equalTo(['env' => $this->environment->id,])
            );

        $out = $this->command->create("mysite.{$this->environment->id}", $params);
        $this->assertNull($out);
    }

    /**
     * Tests the backup:create command when the workflow fails
     */
    public function testCreateBackupFailure()
    {
        $this->environment->id = 'env_id';

        $this->backups->expects($this->once())
            ->method('create')
            ->with($this->equalTo(['element' => null, 'keep-for' => 365,]))
            ->will($this->throwException(new TerminusException()));

        $this->logger->expects($this->never())
            ->method('log');

        $this->setExpectedException(TerminusException::class);

        $out = $this->command->create("mysite.{$this->environment->id}");
        $this->assertNull($out);
    }
}
