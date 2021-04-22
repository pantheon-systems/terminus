<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\Backups;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Backup;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class BackupsTest
 * Testing class for Pantheon\Terminus\Collections\Backups
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class BackupsTest extends CollectionTestCase
{
    /**
     * @var object
     */
    protected $backup_data;
    /**
     * @var Backups
     */
    protected $backups;
    /**
     * @var Environment
     */
    protected $environment;

    public function setUp()
    {
        parent::setUp();

        $this->backup_data = (object)[
            '1471562180_backup_code' =>
                (object)[
                    'task_id' => 'c5ea7af2-6599-11e6-965c-bc764e10d7c2',
                    'finish_time' => 1471562190.7729061,
                    'endpoint_uuid' => '07988ae6-f85e-4563-8a76-a35984294006',
                    'BUILD_URL' => 'https://104.239.201.18:8090/jenkins/job/backup_priority/708812/',
                    'filename' => 'behat-tests_dev_2016-08-18T23-16-20_UTC_code.tar.gz',
                    'BUILD_TAG' => 'jenkins-backup_priority-708812',
                    'ttl' => 31556736,
                    'folder' => '1471562180_backup',
                    'start_time' => 1471562183.1445751,
                    'size' => 33333110,
                    'timestamp' => 1471562190,
                    'scheduled_for' => 1471562190,
                ],
                '1471562156_backup_manifest' =>
                (object)[
                    'total_dirs' => 0,
                    'total_size' => 0,
                    'endpoint_uuid' => '49baf0a5-4386-4b33-839e-350a1ed091fa',
                    'BUILD_URL' => 'https://54.167.154.236:8090/jenkins/job/backup_priority/5618/',
                    'BUILD_TAG' => 'jenkins-backup_priority-5618',
                    'ttl' => 31536000,
                    'total_files' => 0,
                    'folder' => '1471562156_backup',
                    'total_entries' => 0,
                    'size' => 0,
                    'timestamp' => 1471562160,
                    'scheduled_for' => 1471562190,
                ],
                '1471562156_backup_files' =>
                (object)[
                    'task_id' => 'b79ca7fe-6599-11e6-84b4-bc764e1141f9',
                    'finish_time' => 1471562160.4710801,
                    'endpoint_uuid' => '49baf0a5-4386-4b33-839e-350a1ed091fa',
                    'BUILD_URL' => 'https://54.167.154.236:8090/jenkins/job/backup_priority/5618/',
                    'filename' => 'behat-tests_dev_2016-08-18T23-15-56_UTC_files.tar.gz',
                    'BUILD_TAG' => 'jenkins-backup_priority-5618',
                    'ttl' => 31556736,
                    'folder' => '1471562156_backup',
                    'start_time' => 1471562159.2395351,
                    'size' => 168,
                    'timestamp' => 1471562160,
                    'scheduled_for' => 1471562190,
                ],
                '1471562156_backup_database' =>
                (object)[
                    'task_id' => 'b78fe4f6-6599-11e6-84b4-bc764e1141f9',
                    'finish_time' => 1471562159.6410241,
                    'endpoint_uuid' => '07988ae6-f85e-4563-8a76-a35984294006',
                    'BUILD_URL' => 'https://104.239.201.18:8090/jenkins/job/backup_priority/708810/',
                    'filename' => 'behat-tests_dev_2016-08-18T23-15-56_UTC_database.sql.gz',
                    'BUILD_TAG' => 'jenkins-backup_priority-708810',
                    'ttl' => 31556736,
                    'folder' => '1471562156_backup',
                    'start_time' => 1471562158.974858,
                    'size' => 833,
                    'timestamp' => 1471562159,
                    'scheduled_for' => 1471562190,
                ],
                '1471562156_backup_code' =>
                (object)[
                    'task_id' => 'b78dee76-6599-11e6-84b4-bc764e1141f9',
                    'finish_time' => 1471562166.041888,
                    'endpoint_uuid' => '07988ae6-f85e-4563-8a76-a35984294006',
                    'BUILD_URL' => 'https://104.239.201.18:8090/jenkins/job/backup_priority/708811/',
                    'filename' => 'behat-tests_dev_2016-08-18T23-15-56_UTC_code.tar.gz',
                    'BUILD_TAG' => 'jenkins-backup_priority-708811',
                    'ttl' => 31556736,
                    'folder' => '1471562156_backup',
                    'start_time' => 1471562158.947875,
                    'size' => 33358038,
                    'timestamp' => 1471562166,
                    'scheduled_for' => 1471562190,
                ],
                '1471562114_backup_manifest' =>
                (object)[
                    'total_dirs' => 0,
                    'total_size' => 0,
                    'endpoint_uuid' => 'bee94730-4287-4228-857f-ef25b4ce38a3',
                    'BUILD_URL' => 'https://54.166.122.7:8090/jenkins/job/backup_priority/30641/',
                    'BUILD_TAG' => 'jenkins-backup_priority-30641',
                    'ttl' => 31536000,
                    'total_files' => 0,
                    'folder' => '1471562114_backup',
                    'total_entries' => 0,
                    'size' => 0,
                    'timestamp' => 1471562119,
                    'scheduled_for' => 1471562190,
                ],
                '1471562114_backup_files' =>
                (object)[
                    'task_id' => '9e8f4a5a-6599-11e6-977c-bc764e105ecb',
                    'finish_time' => null,
                    'endpoint_uuid' => 'bee94730-4287-4228-857f-ef25b4ce38a3',
                    'BUILD_URL' => 'https://54.166.122.7:8090/jenkins/job/backup_priority/30641/',
                    'filename' => 'behat-tests_dev_2016-08-18T23-15-14_UTC_files.tar.gz',
                    'BUILD_TAG' => 'jenkins-backup_priority-30641',
                    'ttl' => 31556736,
                    'folder' => '1471562114_backup',
                    'start_time' => 1471562118.104852,
                    'size' => 169,
                    'timestamp' => null,
                    'scheduled_for' => 1471562190,
                ],
                '1471562114_backup_database' =>
                (object)[
                    'task_id' => '9e6f55d8-6599-11e6-977c-bc764e105ecb',
                    'finish_time' => 1471562120.2322991,
                    'endpoint_uuid' => '07988ae6-f85e-4563-8a76-a35984294006',
                    'BUILD_URL' => 'https://104.239.201.18:8090/jenkins/job/backup_priority/708807/',
                    'filename' => 'behat-tests_dev_2016-08-18T23-15-14_UTC_database.sql.gz',
                    'BUILD_TAG' => 'jenkins-backup_priority-708807',
                    'ttl' => 31556736,
                    'folder' => '1471562114_backup',
                    'start_time' => 1471562118.301039,
                    'size' => 0,
                    'timestamp' => 1471562120,
                    'scheduled_for' => 1471562190,
                ],
                '1471562114_backup_code' =>
                (object)[
                    'task_id' => '9e6d6e76-6599-11e6-977c-bc764e105ecb',
                    'finish_time' => 1471562126.0202169,
                    'endpoint_uuid' => '07988ae6-f85e-4563-8a76-a35984294006',
                    'BUILD_URL' => 'https://104.239.201.18:8090/jenkins/job/backup_priority/708806/',
                    'filename' => 'behat-tests_dev_2016-08-18T23-15-14_UTC_code.tar.gz',
                    'BUILD_TAG' => 'jenkins-backup_priority-708806',
                    'ttl' => 31556736,
                    'folder' => '1471562114_backup',
                    'start_time' => 1471562118.0184841,
                    'size' => 0,
                    'timestamp' => 1471562126,
                    'scheduled_for' => 1471562190,
                ],
        ];

        $this->backups = $this->createBackups();
    }

    public function testCancelBackupSchedule()
    {
        for ($i = 0; $i < 7; $i++) {
            $this->request->expects($this->at($i))
                ->method('request')
                ->with('sites/abc/environments/dev/backups/schedule/' . $i, ['method' => 'delete']);
        }

        $this->backups->cancelBackupSchedule();
    }

    public function testCreate()
    {
        $backups = $this->createBackups();
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                'do_export',
                ['params' => [
                    'code'       => true,
                    'database'   => true,
                    'files'      => true,
                    'entry_type' => 'backup',
                    'ttl'        => 31536000.0
                ]]
            )
            ->willReturn($this->workflow);

        $actual = $backups->create();
        $this->assertEquals($this->workflow, $actual);

        $backups = $this->createBackups();
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                'do_export',
                ['params' => [
                    'code'       => true,
                    'database'   => false,
                    'files'      => false,
                    'entry_type' => 'backup',
                    'ttl'        => 5 * 86400
                ]]
            )
            ->willReturn($this->workflow);

        $actual = $backups->create(['element' => 'code', 'keep-for' => 5]);
        $this->assertEquals($this->workflow, $actual);

        $backups = $this->createBackups();
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                'do_export',
                ['params' => [
                    'code'       => false,
                    'database'   => true,
                    'files'      => false,
                    'entry_type' => 'backup',
                    'ttl'        => 5 * 86400
                ]]
            )
            ->willReturn($this->workflow);

        $actual = $backups->create(['element' => 'database', 'keep-for' => 5]);
        $this->assertEquals($this->workflow, $actual);

        $backups = $this->createBackups();
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                'do_export',
                ['params' => [
                    'code'       => false,
                    'database'   => false,
                    'files'      => true,
                    'entry_type' => 'backup',
                    'ttl'        => 5 * 86400
                ]]
            )
            ->willReturn($this->workflow);

        $actual = $backups->create(['element' => 'files', 'keep-for' => 5]);
        $this->assertEquals($this->workflow, $actual);
    }

    public function testGetBackupByFileName()
    {
        $backups = $this->createBackupsWithModels();

        $id = '1471562156_backup_code';
        $data = $this->backup_data->{$id};
        $out = $backups->getBackupByFileName($data->filename);
        $this->assertEquals($out->get('id'), $id);
        $this->assertEquals($out->get('folder'), $data->folder);
        $this->assertEquals($out->get('task_id'), $data->task_id);
        $this->assertEquals($out->get('filename'), $data->filename);

        $this->setExpectedException(TerminusException::class, "Could not find a backup identified by not-there.");
        $out = $backups->getBackupByFileName('not-there');
        $this->assertNull($out);
    }

    public function testGetBackupsByElement()
    {
        $backups = $this->createBackupsWithModels();
        $out = $backups->getBackupsByElement('code');
        $this->assertEquals(3, count($out));
        foreach ($out as $backup) {
            $this->assertEquals('code', $backup->get('type'));
        }

        $out = $backups->getBackupsByElement('files');
        $this->assertEquals(2, count($out));
        foreach ($out as $backup) {
            $this->assertEquals('files', $backup->get('type'));
        }

        $out = $backups->getBackupsByElement('database');
        $this->assertEquals(2, count($out));
        foreach ($out as $backup) {
            $this->assertEquals('database', $backup->get('type'));
        }
    }

    public function testGetBackupSchedule()
    {
        $schedule = (object)([
            '0' =>
                (object)([
                    'hour' => 16,
                    'ttl' => 691200,
                ]),
                '1' =>
                (object)([
                    'hour' => 16,
                    'ttl' => 691200,
                ]),
                '2' =>
                (object)([
                    'hour' => 16,
                    'ttl' => 691200,
                ]),
                '3' =>
                (object)([
                    'hour' => 16,
                    'ttl' => 691200,
                ]),
                '4' =>
                (object)([
                    'hour' => 16,
                    'ttl' => 691200,
                ]),
                '5' =>
                (object)([
                    'hour' => 16,
                    'ttl' => 2764800,
                ]),
                '6' =>
                (object)([
                    'hour' => 16,
                    'ttl' => 691200,
                ]),
        ]);
        $backups = $this->createBackups();
        $this->request->expects($this->once())
            ->method('request')
            ->with('sites/abc/environments/dev/backups/schedule')
            ->willReturn(['data' => $schedule]);

        $actual = $backups->getBackupSchedule();
        $expected = [
            'daily_backup_hour' => '16 UTC',
            'weekly_backup_day' => 'Friday',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testGetFinishedBackups()
    {
        $backups = $this->createBackupsWithModels();

        $out = $backups->getFinishedBackups();
        $this->assertEquals(4, count($out));
        $last = INF;
        foreach ($out as $backup) {
            $this->assertTrue($backup->backupIsFinished());
            $this->assertLessThan($last, $backup->get('start_time'));
            $last = $backup->get('start_time');
        }

        $out = $backups->getFinishedBackups('code');
        $this->assertEquals(2, count($out));
        foreach ($out as $backup) {
            $this->assertTrue($backup->backupIsFinished());
            $this->assertEquals('code', $backup->get('type'));
        }
        $out = $backups->getFinishedBackups('files');
        $this->assertEquals(1, count($out));
        foreach ($out as $backup) {
            $this->assertTrue($backup->backupIsFinished());
            $this->assertEquals('files', $backup->get('type'));
        }
        $out = $backups->getFinishedBackups('database');
        $this->assertEquals(1, count($out));
        foreach ($out as $backup) {
            $this->assertTrue($backup->backupIsFinished());
            $this->assertEquals('database', $backup->get('type'));
        }
    }

    public function testGetValidElements()
    {
        $expected = ['code', 'files', 'database', 'db',];
        $actual = $this->backups->getValidElements();
        $this->assertEquals($expected, $actual);
    }

    public function testSetBackupSchedule()
    {
        $backups = $this->createBackups();
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                'change_backup_schedule',
                ['params' => [
                    'backup_schedule' => (object)[
                        (object)['hour' => null, 'ttl' => Backups::WEEKLY_BACKUP_TTL],
                        (object)['hour' => null, 'ttl' => Backups::DAILY_BACKUP_TTL],
                        (object)['hour' => null, 'ttl' => Backups::DAILY_BACKUP_TTL],
                        (object)['hour' => null, 'ttl' => Backups::DAILY_BACKUP_TTL],
                        (object)['hour' => null, 'ttl' => Backups::DAILY_BACKUP_TTL],
                        (object)['hour' => null, 'ttl' => Backups::DAILY_BACKUP_TTL],
                        (object)['hour' => null, 'ttl' => Backups::DAILY_BACKUP_TTL],
                    ]
                ]]
            )
            ->willReturn($this->workflow);

        $actual = $backups->setBackupSchedule(['day' => 'Sunday',]);
        $this->assertEquals($this->workflow, $actual);

        $backups = $this->createBackups();
        $this->workflows->expects($this->once())
            ->method('create')
            ->with(
                'change_backup_schedule',
                ['params' => [
                    'backup_schedule' => (object)[
                        (object)['hour' => 5, 'ttl' => Backups::DAILY_BACKUP_TTL,],
                        (object)['hour' => 5, 'ttl' => Backups::WEEKLY_BACKUP_TTL,],
                        (object)['hour' => 5, 'ttl' => Backups::DAILY_BACKUP_TTL,],
                        (object)['hour' => 5, 'ttl' => Backups::DAILY_BACKUP_TTL,],
                        (object)['hour' => 5, 'ttl' => Backups::DAILY_BACKUP_TTL,],
                        (object)['hour' => 5, 'ttl' => Backups::DAILY_BACKUP_TTL,],
                        (object)['hour' => 5, 'ttl' => Backups::DAILY_BACKUP_TTL,],
                    ]
                ]]
            )
            ->willReturn($this->workflow);

        $actual = $backups->setBackupSchedule(['day' => 'Monday', 'hour' => 5,]);
        $this->assertEquals($this->workflow, $actual);
    }

    protected function createBackups()
    {
        $site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site->id = 'abc';
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->id = 'dev';
        $this->environment->method('getWorkflows')->willReturn($this->workflows);
        $this->environment->method('getSite')->willReturn($site);

        $backups = new Backups(['environment' => $this->environment]);
        $backups->setRequest($this->request);
        $backups->setContainer($this->container);
        return $backups;
    }

    protected function createBackupsWithModels()
    {
        $backups = $this->createBackups();
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                'sites/abc/environments/dev/backups/catalog',
                ['options' => ['method' => 'get',],]
            )
            ->willReturn(['data' => $this->backup_data,]);

        $i = 0;
        foreach ((array)$this->backup_data as $id => $data) {
            if (isset($data->filename)) {
                $this->container->expects($this->at($i++))
                    ->method('get')
                    ->with(
                        Backup::class,
                        [
                            $data,
                            ['id' => $id, 'collection' => $backups,]
                        ]
                    )
                    ->willReturn(
                        new Backup(
                            (object)array_merge((array)$data, compact('id')),
                            ['collection' => $backups,]
                        )
                    );
            }
        }
        return $backups;
    }
}
