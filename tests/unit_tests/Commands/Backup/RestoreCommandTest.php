<?php

namespace Pantheon\Terminus\UnitTests\Commands\Backup;

use Pantheon\Terminus\Commands\Backup\RestoreCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class RestoreCommandTest
 * Testing class for Pantheon\Terminus\Commands\Backup\RestoreCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Backup
 */
class RestoreCommandTest extends BackupCommandTest
{
    use WorkflowProgressTrait;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new RestoreCommand($this->sites);
        $this->command->setContainer($this->getContainer());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->command->setInput($this->input);
        $this->expectWorkflowProcessing();
    }

    /**
     * Tests the backup:restore command with file when the restoration is successful
     */
    public function testRestoreBackupWithFileSucceeds()
    {
        $this->environment->id = 'env_id';
        $test_filename = 'test.tar.gz';

        $this->backups->expects($this->once())
            ->method('getBackupByFileName')
            ->with($test_filename)
            ->willReturn($this->backup);
        $this->expectConfirmation();

        $this->backup->expects($this->once())
            ->method('restore')
            ->willReturn($this->workflow);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Restored the backup to {env}.'),
                $this->equalTo(['env' => $this->environment->id,])
            );

        $out = $this->command->restoreBackup("mysite.{$this->environment->id}", ['file' => $test_filename,]);
        $this->assertNull($out);
    }

    /**
     * Tests the backup:restore command with file when the restoration is unsuccessful
     */
    public function testRestoreBackupWithFileFails()
    {
        $this->environment->id = 'env_id';
        $test_filename = 'test.tar.gz';
        $message = 'Successfully queued restore_site';
        $better_message = 'There was an error while restoring your backup.';

        $this->backups->expects($this->once())
            ->method('getBackupByFileName')
            ->with($test_filename)
            ->willReturn($this->backup);
        $this->expectConfirmation();

        $this->backup->expects($this->once())
            ->method('restore')
            ->willReturn($this->workflow);

        $this->progress_bar->method('cycle')
            ->with()
            ->will($this->throwException(new TerminusException($message)));

        $this->logger->expects($this->never())
            ->method('log');

        $this->workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn($message);

        $this->setExpectedException(TerminusException::class, $better_message);

        $out = $this->command->restoreBackup("mysite.{$this->environment->id}", ['file' => $test_filename,]);
        $this->assertNull($out);
    }

    /**
     * Tests the backup:restore command with file that doesn't exist
     */
    public function testRestoreBackupWithInvalidFile()
    {
        $bad_file_name = 'no-file.tar.gz';

        $this->backups->expects($this->once())
            ->method('getBackupByFileName')
            ->with($this->equalTo($bad_file_name))
            ->will($this->throwException(new TerminusNotFoundException()));

        $this->setExpectedException(TerminusNotFoundException::class);

        $out = $this->command->restoreBackup('mysite.dev', ['file' => $bad_file_name,]);
        $this->assertNull($out);
    }

    /**
     * Tests the backup:restore command with an element when the backup operation succeeds
     */
    public function testRestoreBackupWithElementSucceeds()
    {
        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->with('database')
            ->willReturn([$this->backup,]);
        $this->expectConfirmation();

        $this->backup->expects($this->once())
            ->method('restore')
            ->willReturn($this->workflow);

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Restored the backup to {env}.'),
                $this->equalTo(['env' => $this->environment->id,])
            );

        $out = $this->command->restoreBackup('mysite.dev', ['element' => 'db',]);
        $this->assertNull($out);
    }

    /**
     * Tests the backup:restore command with an element when the backup operation has failed
     */
    public function testRestoreBackupWithElementFails()
    {
        $message = 'Successfully queued restore_site';
        $better_message = 'There was an error while restoring your backup.';

        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->with('database')
            ->willReturn([$this->backup,]);
        $this->expectConfirmation();

        $this->backup->expects($this->once())
            ->method('restore')
            ->willReturn($this->workflow);

        $this->progress_bar->method('cycle')
            ->with()
            ->will($this->throwException(new TerminusException($message)));

        $this->logger->expects($this->never())
            ->method('log');

        $this->workflow->expects($this->once())
            ->method('getMessage')
            ->with()
            ->willReturn($message);

        $this->setExpectedException(TerminusException::class, $better_message);

        $out = $this->command->restoreBackup('mysite.dev', ['element' => 'db',]);
        $this->assertNull($out);
    }

    /**
     * Tests the backup:restore command when there isn't a backup to restore
     */
    public function testRestoreNoBackups()
    {
        $site_name = 'site name';
        $this->environment->id = 'dev';
        $element = 'code';

        $this->backups->expects($this->once())
            ->method('getFinishedBackups')
            ->with($this->equalTo($element))
            ->willReturn([]);

        $this->backup->expects($this->never())->method('restore');
        $this->logger->expects($this->never())->method('log');
        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($site_name);
        $this->setExpectedException(
            TerminusNotFoundException::class,
            "No backups available. Create one with `terminus backup:create $site_name.{$this->environment->id}`"
        );

        $out = $this->command->restoreBackup("$site_name.{$this->environment->id}", compact('element'));
        $this->assertNull($out);
    }
}
