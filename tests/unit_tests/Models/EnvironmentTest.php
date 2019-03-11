<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\Backups;
use Pantheon\Terminus\Collections\Bindings;
use Pantheon\Terminus\Collections\Domains;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Models\Binding;
use Pantheon\Terminus\Models\Domain;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Lock;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\UpstreamStatus;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Collections\Commits;
use Pantheon\Terminus\Models\Commit;
use Pantheon\Terminus\Exceptions\TerminusException;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Process\ProcessUtils;

/**
 * Class EnvironmentTest
 * Testing class for Pantheon\Terminus\Models\Environment
 * @package Pantheon\Terminus\UnitTests\Models
 */
class EnvironmentTest extends ModelTestCase
{
    /**
     * @var Backups
     */
    protected $backups;
    /**
     * @var Binding
     */
    protected $binding;
    /**
     * @var Bindings
     */
    protected $bindings;
    /**
     * @var Commits
     */
    protected $commits;
    /**
     * @var Container
     */
    protected $container;
    /**
     * @var Domains
     */
    protected $domains;
    /**
     * @var LocalMachineHelper
     */
    protected $local_machine;
    /**
     * @var Lock
     */
    protected $lock;
    /**
     * @var UpstreamStatus
     */
    protected $upstream_status;
    /**
     * @var Workflow
     */
    protected $workflow;
    /**
     * @var Workflows
     */
    protected $workflows;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->model = $this->createModel(['id' => 'dev',]);
    }

    public function testApplyUpstreamUpdates()
    {
        $this->setUpWorkflowOperationTest(
            'applyUpstreamUpdates',
            [],
            'apply_upstream_updates',
            ['updatedb' => true, 'xoption' => false,]
        );
        $this->setUpWorkflowOperationTest(
            'applyUpstreamUpdates',
            [false, true,],
            'apply_upstream_updates',
            ['updatedb' => false, 'xoption' => true,]
        );
    }

    public function testCacheserverConnectionInfo()
    {
        $this->model->id = 'env id';
        $this->binding->id = 'binding id';
        $this->site->id = 'site id';
        $password = 'password';
        $port = 'port';
        $domain = 'domain';
        $expected = [
            'password' => $password,
            'host' => $domain,
            'port' => $port,
            'url' => "redis://pantheon:$password@$domain:$port",
            'command' => "redis-cli -h $domain -p $port -a $password",
        ];

        $this->bindings->expects($this->once())
            ->method('getByType')
            ->with($this->equalTo('cacheserver'))
            ->willReturn([$this->binding,]);
        $this->binding->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('environment'))
            ->willReturn($this->model->id);
        $this->binding->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('password'))
            ->willReturn($password);
        $this->binding->expects($this->at(2))
            ->method('get')
            ->with($this->equalTo('host'))
            ->willReturn($domain);
        $this->binding->expects($this->at(3))
            ->method('get')
            ->with($this->equalTo('port'))
            ->willReturn($port);

        $out = $this->model->cacheserverConnectionInfo();
        $this->assertEquals($expected, $out);
    }

    /**
     * Tests Environment::cacheserverConnectionInfo() when there are no DB servers.
     */
    public function testCacheserverConnectionInfoEmpty()
    {
        $this->bindings->expects($this->once())
            ->method('getByType')
            ->with($this->equalTo('cacheserver'))
            ->willReturn([]);
        $out = $this->model->cacheserverConnectionInfo();
        $this->assertEquals([], $out);
    }

    public function testChangeConnectionModeToGit()
    {
        $this->setUpWorkflowOperationTest(
            'changeConnectionMode',
            ['git'],
            'enable_git_mode',
            null,
            ['id' => 'dev', 'on_server_development' => true]
        );
    }

    public function testChangeConnectionModeToSFTP()
    {
        $this->setUpWorkflowOperationTest(
            'changeConnectionMode',
            ['sftp'],
            'enable_on_server_development',
            null,
            ['id' => 'dev']
        );
    }

    public function testChangeConnectionModeToSame()
    {
        $model = $this->createModel(['id' => 'dev', 'on_server_development' => true,]);
        $this->setExpectedException(
            TerminusException::class,
            'The connection mode is already set to sftp.'
        );
        $this->assertNull($model->changeConnectionMode('sftp'));
    }

    public function testChangeConnectionModeToInvalid()
    {
        $model = $this->createModel(['id' => 'dev', 'on_server_development' => true,]);
        $this->setExpectedException(
            TerminusException::class,
            'You must specify the mode as either sftp or git.'
        );
        $this->assertNull($model->changeConnectionMode('doggo'));
    }

    public function testClearCache()
    {
        $this->setUpWorkflowOperationTest(
            'clearCache',
            [],
            'clear_cache',
            ['framework_cache' => true,]
        );
    }

    public function testCloneDatabase()
    {
        $this->setUpWorkflowOperationTest(
            'cloneDatabase',
            [$this->model,],
            'clone_database',
            ['from_environment' => 'dev',]
        );
        $this->setUpWorkflowOperationTest(
            'cloneDatabase',
            [$this->model,],
            'clone_database',
            ['from_environment' => 'dev',]
        );
    }

    public function testCloneFiles()
    {
        $this->setUpWorkflowOperationTest(
            'cloneFiles',
            [$this->model,],
            'clone_files',
            ['from_environment' => 'dev',]
        );
        $this->setUpWorkflowOperationTest(
            'cloneFiles',
            [$this->model,],
            'clone_files',
            ['from_environment' => 'dev',]
        );
    }

    public function testCommitChanges()
    {
        $this->workflows->expects($this->any())
            ->method('create')
            ->with(
                'commit_and_push_on_server_changes',
                ['params' =>
                    [
                        'message' => 'Hello, World!',
                        'committer_name' => 'Dev Tester',
                        'committer_email' => 'dev@example.com'
                    ]
                ]
            )
            ->willReturn($this->workflow);

        $this->local_machine->expects($this->at(0))
            ->method('exec')
            ->with('git config user.email')
            ->willReturn(['output' => 'dev@example.com', 'exit_code' => 0]);

        $this->local_machine->expects($this->at(1))
            ->method('exec')
            ->with('git config user.name')
            ->willReturn(['output' => 'Dev Tester', 'exit_code' => 0]);

        $actual = $this->model->commitChanges('Hello, World!');
        $this->assertEquals($this->workflow, $actual);
    }

    public function testConnectionInfo()
    {
        $this->model->id = 'env id';
        $this->binding->id = 'binding id';
        $this->site->id = 'site id';
        $password = 'password';
        $port = '2222';
        $username = $database = 'pantheon';
        $sftp_username = "{$this->model->id}.{$this->site->id}";
        $sftp_domain = "appserver.$sftp_username.drush.in";
        $db_domain = "dbserver.{$this->model->id}.{$this->model->getSite()->id}.drush.in";
        $cache_domain = 'domain';
        $git_domain = "codeserver.dev.{$this->site->id}.drush.in";
        $git_username = "codeserver.dev.{$this->site->id}";

        $sftp_expected = [
            'sftp_username' => $sftp_username,
            'sftp_host' => $sftp_domain,
            'sftp_port' => '2222',
            'sftp_password' => 'Use your account password',
            'sftp_url' => "sftp://$sftp_username@$sftp_domain:$port",
            'sftp_command' => "sftp -o Port=$port $sftp_username@$sftp_domain",
        ];

        $this->bindings->expects($this->at(0))
            ->method('getByType')
            ->with($this->equalTo('dbserver'))
            ->willReturn([$this->binding,]);
        $this->binding->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('environment'))
            ->willReturn($this->model->id);
        $this->binding->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('password'))
            ->willReturn($password);
        $this->binding->expects($this->at(2))
            ->method('get')
            ->with($this->equalTo('port'))
            ->willReturn($port);

        $db_expected = [
            'mysql_username' => $username,
            'mysql_password' => $password,
            'mysql_host' => $db_domain,
            'mysql_port' => $port,
            'mysql_database' => $database,
            'mysql_url' => "mysql://$username:$password@$db_domain:$port/$database",
            'mysql_command' => "mysql -u $username -p$password -h $db_domain -P $port $database",
        ];

        $this->bindings->expects($this->at(1))
            ->method('getByType')
            ->with($this->equalTo('cacheserver'))
            ->willReturn([$this->binding,]);
        $this->binding->expects($this->at(3))
            ->method('get')
            ->with($this->equalTo('environment'))
            ->willReturn($this->model->id);
        $this->binding->expects($this->at(4))
            ->method('get')
            ->with($this->equalTo('password'))
            ->willReturn($password);
        $this->binding->expects($this->at(5))
            ->method('get')
            ->with($this->equalTo('host'))
            ->willReturn($cache_domain);
        $this->binding->expects($this->at(6))
            ->method('get')
            ->with($this->equalTo('port'))
            ->willReturn($port);

        $cache_expected = [
            'redis_password' => $password,
            'redis_host' => $cache_domain,
            'redis_port' => $port,
            'redis_url' => "redis://pantheon:$password@$cache_domain:$port",
            'redis_command' => "redis-cli -h $cache_domain -p $port -a $password",
        ];

        $git_expected = [
            'git_username' => $git_username,
            'git_host' => $git_domain,
            'git_port' => $port,
            'git_url' => "ssh://$git_username@$git_domain:$port/~/repository.git",
            'git_command' => "git clone ssh://$git_username@$git_domain:$port/~/repository.git",
        ];

        $out = $this->model->connectionInfo();
        $this->assertEquals(array_merge($sftp_expected, $db_expected, $cache_expected, $git_expected), $out);
    }

    public function testConvergeBindings()
    {
        $this->setUpWorkflowOperationTest(
            'convergeBindings',
            [],
            'converge_environment'
        );
    }

    public function testCountDeployableCode()
    {
        $model = $this->getModelWithCommits([]);
        $this->assertFalse($model->hasDeployableCode());
        $model = $this->getModelWithCommits([]);
        $this->assertEquals(0, $model->hasDeployableCode());

        $commits = [
            ['not', 'deployable',],
            ['also', 'not', 'deployable',],
            ['live', 'not', 'deployable',],
            ['dev', 'not', 'deployable',],
        ];
        $model = $this->getModelWithCommits($commits);
        $this->assertFalse($model->hasDeployableCode());
        $model = $this->getModelWithCommits($commits);
        $this->assertEquals(0, $model->hasDeployableCode());

        $commits = [
            ['not', 'deployable',],
            ['also', 'not', 'deployable',],
            ['not-deployable', 'live', 'test',],
            ['also', 'deployable', 'test',],
        ];
        $model = $this->getModelWithCommits($commits);
        $this->assertTrue($model->hasDeployableCode());
        $model = $this->getModelWithCommits($commits);
        $this->assertEquals(1, $model->hasDeployableCode());

        $commits = [
            ['not', 'deployable',],
            ['also', 'not', 'deployable',],
            ['deployable', 'test',],
            ['also', 'deployable', 'test',],
        ];
        $model = $this->getModelWithCommits($commits);
        $this->assertTrue($model->hasDeployableCode());
        $model = $this->getModelWithCommits($commits);
        $this->assertEquals(2, $model->hasDeployableCode());
    }

    public function testDashboardUrl()
    {
        $this->site->expects($this->once())
            ->method('dashboardUrl')
            ->willReturn('https://example.com/sites/abc');
        $actual = $this->model->dashboardUrl();
        $expected = "https://example.com/sites/abc#dev";
        $this->assertEquals($expected, $actual);
    }

    public function testDatabaseConnectionInfo()
    {
        $this->model->id = 'env id';
        $this->binding->id = 'binding id';
        $this->site->id = 'site id';
        $password = 'password';
        $port = 'port';
        $username = $database = 'pantheon';
        $domain = "dbserver.{$this->model->id}.{$this->model->getSite()->id}.drush.in";
        $expected = [
            'username' => $username,
            'password' => $password,
            'host' => $domain,
            'port' => $port,
            'database' => $database,
            'url' => "mysql://$username:$password@$domain:$port/$database",
            'command' => "mysql -u $username -p$password -h $domain -P $port $database",
        ];

        $this->bindings->expects($this->once())
            ->method('getByType')
            ->with($this->equalTo('dbserver'))
            ->willReturn([$this->binding,]);
        $this->binding->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('environment'))
            ->willReturn($this->model->id);
        $this->binding->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('password'))
            ->willReturn($password);
        $this->binding->expects($this->at(2))
            ->method('get')
            ->with($this->equalTo('port'))
            ->willReturn($port);

        $out = $this->model->databaseConnectionInfo();
        $this->assertEquals($expected, $out);
    }

    /**
     * Tests Environment::databaseConnectionInfo() when there are no DB servers.
     */
    public function testDatabaseConnectionInfoEmpty()
    {
        $this->bindings->expects($this->once())
            ->method('getByType')
            ->with($this->equalTo('dbserver'))
            ->willReturn([]);
        $out = $this->model->databaseConnectionInfo();
        $this->assertEquals([], $out);
    }


    public function testDelete()
    {
        $model = $this->createModel(['id' => 'mymulti']);
        $this->site->method('getWorkflows')->willReturn($this->workflows);
        $this->workflows->expects($this->any())
            ->method('create')
            ->with(
                'delete_cloud_development_environment',
                ['params' => ['environment_id' => 'mymulti', 'delete_branch' => false]]
            )
            ->willReturn($this->workflow);

        $actual = $model->delete();
        $this->assertEquals($this->workflow, $actual);
    }

    public function testDeleteWithBranch()
    {
        $model = $this->createModel(['id' => 'mymulti2']);
        $this->site->method('getWorkflows')->willReturn($this->workflows);
        $this->workflows->expects($this->any())
            ->method('create')
            ->with(
                'delete_cloud_development_environment',
                ['params' => ['environment_id' => 'mymulti2', 'delete_branch' => true]]
            )
            ->willReturn($this->workflow);

        $actual = $model->delete(['delete_branch' => true]);
        $this->assertEquals($this->workflow, $actual);
    }

    public function testDeploy()
    {
        $this->setUpWorkflowOperationTest(
            'deploy',
            [['a' => '123', 'b' => '345',],],
            'deploy',
            ['a' => '123', 'b' => '345',]
        );
    }

    public function testDiffstat()
    {
        $expected = ['foo' => 'bar',];
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                'sites/abc/environments/dev/on-server-development/diffstat',
                ['method' => 'get',]
            )
            ->willReturn(['data' => $expected]);
        $actual = $this->model->diffStat();
        $this->assertEquals($expected, $actual);
    }

    public function testDisableHttpsCertificate()
    {
        $this->request->expects($this->at(0))
            ->method('request')
            ->with(
                'sites/abc/environments/dev/settings',
                ['method' => 'get',]
            )
            ->willReturn(['data' => (object)['ssl_enabled' => true,]]);

        $this->request->expects($this->at(1))
            ->method('request')
            ->with(
                'sites/abc/environments/dev/settings',
                [
                    'method' => 'put',
                    'form_params' => [
                        'ssl_enabled' => false,
                        'dedicated_ip' => false,
                    ],
                ]
            )
            ->willReturn(['data' => 'Ok',]);

        $this->model->disableHttpsCertificate();
    }

    public function testDisableHttpsCertificateFailed()
    {
        $this->request->expects($this->at(0))
            ->method('request')
            ->with(
                'sites/abc/environments/dev/settings',
                ['method' => 'get',]
            )
            ->willReturn(['data' => (object)['ssl_enabled' => true,],]);

        $this->request->expects($this->at(1))
            ->method('request')
            ->with(
                'sites/abc/environments/dev/settings',
                [
                    'method' => 'put',
                    'form_params' => [
                        'ssl_enabled' => false,
                        'dedicated_ip' => false,
                    ],
                ]
            )
            ->will($this->throwException(new \Exception()));

        $this->setExpectedException(TerminusException::class, 'There was an problem disabling https for this environment.');
        $this->model->disableHttpsCertificate();
    }

    public function testDisableHttpsCertificateNotEnabled()
    {
        $this->request->expects($this->at(0))
            ->method('request')
            ->with(
                'sites/abc/environments/dev/settings',
                ['method' => 'get']
            )
            ->willReturn(['data' => (object)['ssl_enabled' => false,],]);

        $this->setExpectedException(TerminusException::class, 'The dev environment does not have https enabled.');
        $this->model->disableHttpsCertificate();
    }

    public function testDomain()
    {
        $model = $this->createModel(['id' => 'dev', 'dns_zone' => 'example.com',]);
        $expected = "dev-abc.example.com";
        $actual = $model->domain();
        $this->assertEquals($expected, $actual);
    }

    public function testGetBackups()
    {
        $out = $this->model->getBackups();
        $this->assertEquals($this->backups, $out);
    }

    /**
     * Tests Environment::getBranchName()
     */
    public function testGetBranchName()
    {
        $env_id = 'environment id';
        $multidev_env = $this->createModel(['id' => $env_id,]);
        $this->assertEquals($env_id, $multidev_env->getBranchName());
        $this->assertEquals('master', $this->model->getBranchName());
    }

    public function testGetCommits()
    {
        $out = $this->model->getCommits();
        $this->assertEquals($this->commits, $out);
    }

    public function testGetDrushVersion()
    {
        $expected = '8.0';
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                'sites/abc/environments/dev/settings',
                ['method' => 'get',]
            )
            ->willReturn(['data' => (object)['drush_version' => $expected,],]);
        $actual = $this->model->getDrushVersion();
        $this->assertEquals($expected, $actual);
    }

    public function testGetLock()
    {
        $out = $this->model->getLock();
        $this->assertEquals($this->lock, $out);
    }

    public function testGetName()
    {
        $actual = $this->model->getName();
        $this->assertEquals('dev', $actual);
    }

    public function testGetParentEnvironmentDev()
    {
        $this->assertNull($this->model->getParentEnvironment());
    }

    public function testGetParentEnvironmentTest()
    {
        $this->setUpTestGetParentEnvironment('test', 'dev');
    }

    public function testGetParentEnvironmentLive()
    {
        $this->setUpTestGetParentEnvironment('live', 'test');
    }

    public function testGetParentEnvironmentMultiDev()
    {
        $this->setUpTestGetParentEnvironment('mymulti', 'dev');
    }

    public function testGetUpstreamStatus()
    {
        $out = $this->model->getUpstreamStatus();
        $this->assertEquals($this->upstream_status, $out);
    }

    public function testGitConnectionInfo()
    {
        $actual = $this->model->gitConnectionInfo();
        $expected = [
            'username' => 'codeserver.dev.abc',
            'host' => 'codeserver.dev.abc.drush.in',
            'port' => '2222',
            'url' => 'ssh://codeserver.dev.abc@codeserver.dev.abc.drush.in:2222/~/repository.git',
            'command' => 'git clone ssh://codeserver.dev.abc@codeserver.dev.abc.drush.in:2222/~/repository.git',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testImportDatabase()
    {
        $this->setUpWorkflowOperationTest(
            'importDatabase',
            ['https://example.com/myfile.sql',],
            'do_import',
            [
                'url' => 'https://example.com/myfile.sql',
                'database' => 1,
            ]
        );
    }

    public function testImport()
    {
        $this->setUpWorkflowOperationTest(
            'import',
            ['https://example.com/myfile.tar.gz',],
            'do_migration',
            ['url' => 'https://example.com/myfile.tar.gz',]
        );
    }

    public function testImportFiles()
    {
        $this->setUpWorkflowOperationTest(
            'importFiles',
            ['https://example.com/myfile.tar.gz',],
            'do_import',
            [
                'files' => 1,
                'url' => 'https://example.com/myfile.tar.gz',
            ]
        );
    }

    /**
     * Exercises the initializeBindings function
     */
    public function testInitializeBindings()
    {
        $live_copies_from = ['from_environment' => 'test',];
        $test_copies_from = ['from_environment' => 'dev',];

        // Test environment, no message supplied
        $this->setUpWorkflowOperationTest(
            'initializeBindings',
            [],
            'create_environment',
            [
                'annotation' => 'Create the test environment',
                'clone_database' => $test_copies_from,
                'clone_files' => $test_copies_from,
            ],
            ['id' => 'test',]
        );

        // Live environment, no message supplied
        $this->setUpWorkflowOperationTest(
            'initializeBindings',
            [],
            'create_environment',
            [
                'annotation' => 'Create the live environment',
                'clone_database' => $live_copies_from,
                'clone_files' => $live_copies_from,
            ],
            ['id' => 'live',]
        );

        // Test environment, message supplied
        $message_for_test = 'Fighting evil by moonlight';
        $this->setUpWorkflowOperationTest(
            'initializeBindings',
            [['annotation' => $message_for_test,],],
            'create_environment',
            [
                'annotation' => $message_for_test,
                'clone_database' => $test_copies_from,
                'clone_files' => $test_copies_from,
            ],
            ['id' => 'test',]
        );

        // Live environment, message supplied
        $message_for_live = 'Winning love by daylight';
        $this->setUpWorkflowOperationTest(
            'initializeBindings',
            [['annotation' => $message_for_live,],],
            'create_environment',
            [
                'annotation' => $message_for_live,
                'clone_database' => $live_copies_from,
                'clone_files' => $live_copies_from,
            ],
            ['id' => 'live',]
        );
    }

    public function testIsInitialized()
    {
        $commits = $this->getMockBuilder(Commits::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $environments = $this->getMockBuilder(Environments::class)
            ->disableOriginalConstructor()
            ->getMock();
        $container->method('get')->willReturn($commits);
        $environments->method('getSite')->with()->willReturn($this->site);

        $model = new Environment((object)['id' => 'neither test nor live',], ['collection' => $environments,]);
        $this->assertTrue($model->isInitialized());

        $model = new Environment((object)['id' => 'test',], ['collection' => $environments,]);
        $model->setContainer($container);
        $commits->expects($this->once())
            ->method('all')
            ->willReturn([]);
        $this->assertFalse($model->isInitialized());
    }

    public function testIsDevelopment()
    {
        $model = $this->createModel(['id' => 'test',]);
        $this->assertFalse($model->isDevelopment());

        $model = $this->createModel(['id' => 'live',]);
        $this->assertFalse($model->isDevelopment());

        $model = $this->createModel(['id' => 'mymulti',]);
        $this->assertTrue($model->isDevelopment());

        $model = $this->createModel(['id' => 'dev',]);
        $this->assertTrue($model->isDevelopment());
    }

    public function testIsMultidev()
    {
        $this->assertFalse($this->model->isMultidev());

        $model = $this->createModel(['id' => 'test',]);
        $this->assertFalse($model->isMultidev());

        $model = $this->createModel(['id' => 'live',]);
        $this->assertFalse($model->isMultidev());

        $model = $this->createModel(['id' => 'mymulti',]);
        $this->assertTrue($model->isMultidev());
    }

    public function testMergeFromDev()
    {
        $this->setUpWorkflowOperationTest(
            'mergeFromDev',
            [],
            'merge_dev_into_cloud_development_environment',
            ['updatedb' => false,],
            ['id' => 'mymulti',]
        );

        $this->setUpWorkflowOperationTest(
            'mergeFromDev',
            [['updatedb' => true,],],
            'merge_dev_into_cloud_development_environment',
            ['updatedb' => true,],
            ['id' => 'mymulti',]
        );

        $this->setExpectedException(TerminusException::class, 'The dev environment is not a multidev environment');
        $model = $this->createModel(['id' => 'dev',]);
        $model->mergeFromDev();
    }

    public function testMergeToDev()
    {

        $this->setUpWorkflowOperationTest(
            'mergeToDev',
            [],
            'merge_cloud_development_environment_into_dev',
            ['updatedb' => false, 'from_environment' => null,]
        );

        $this->setUpWorkflowOperationTest(
            'mergeToDev',
            [['updatedb' => true, 'from_environment' => 'mymulti',],],
            'merge_cloud_development_environment_into_dev',
            ['updatedb' => true, 'from_environment' => 'mymulti',]
        );

        $this->setExpectedException(
            TerminusException::class,
            'Environment::mergeToDev() may only be run on the dev environment.'
        );
        $model = $this->createModel(['id' => 'stage',]);
        $model->mergeToDev();
    }

    /**
     * Tests the Environment::serialize() function when the environment is in Git mode
     */
    public function testSerializeGitMode()
    {
        $info = [
            'id' => 'dev',
            'environment_created' => '1479413982',
            'on_server_development' => false,
            'php_version' => '70',
            'dns_zone' => 'example.com',
        ];
        $expected = [
            'id' => 'dev',
            'created' => '1479413982',
            'domain' => 'dev-abc.example.com',
            'onserverdev' => false,
            'locked' => false,
            'initialized' => true,
            'connection_mode' => 'git',
            'php_version' => '7.0',
        ];
        $this->lock->method('isLocked')->willReturn(false);
        $this->configSet(['date_format' => 'Y-m-d',]);

        $model = $this->createModel($info);
        $actual = $model->serialize();
        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests the Environment::serialize() function when the environment is in SFTP mode
     */
    public function testSerializeSFTPMode()
    {
        $info = [
            'id' => 'dev',
            'environment_created' => '1479413982',
            'on_server_development' => true,
            'php_version' => '70',
            'dns_zone' => 'example.com',
        ];
        $expected = [
            'id' => 'dev',
            'created' => '1479413982',
            'domain' => 'dev-abc.example.com',
            'onserverdev' => true,
            'locked' => false,
            'initialized' => true,
            'connection_mode' => 'sftp',
            'php_version' => '7.0',
        ];
        $this->lock->method('isLocked')->willReturn(false);
        $this->configSet(['date_format' => 'Y-m-d',]);

        $model = $this->createModel($info);
        $actual = $model->serialize();
        $this->assertEquals($expected, $actual);
    }

    public function testSetHttpsCertificate()
    {
        $expected_params = [
            'cert' => 'CERTIFICATE',
            'intermediary' => 'INTERMEDIARY',
        ];
        $certificate = array_merge($expected_params, ['key' => null,]);
        $this->model->getSite()->id = 'site id';
        $this->model->id = 'env id';
        $response = ['data' => (object)['some' => 'data',],];

        $this->request->expects($this->once())
            ->method('request')
            ->with(
                $this->equalTo("sites/{$this->model->getSite()->id}/environments/{$this->model->id}/add-ssl-cert"),
                $this->equalTo(['method' => 'POST', 'form_params' => $expected_params,])
            )
            ->willReturn($response);

        $out = $this->model->setHttpsCertificate($certificate);
        $this->assertEquals($this->workflow, $out);
    }


    public function testSftpConnectionInfo()
    {
        $actual = $this->model->sftpConnectionInfo();
        $expected = [
            'username' => 'dev.abc',
            'host' => 'appserver.dev.abc.drush.in',
            'port' => '2222',
            'password' => 'Use your account password',
            'url' => 'sftp://dev.abc@appserver.dev.abc.drush.in:2222',
            'command' => 'sftp -o Port=2222 dev.abc@appserver.dev.abc.drush.in',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testSftpConnectionInfoHost()
    {
        $this->configSet(['host' => 'onebox', 'ssh_host' => null,]);
        $actual = $this->model->sftpConnectionInfo();
        $expected = [
            'username' => 'appserver.dev.abc',
            'host' => 'onebox',
            'port' => '2222',
            'password' => 'Use your account password',
            'url' => 'sftp://appserver.dev.abc@onebox:2222',
            'command' => 'sftp -o Port=2222 appserver.dev.abc@onebox',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testSftpConnectionInfoSSHHost()
    {
        $this->configSet(['ssh_host' => 'ssh.example.com',]);
        $actual = $this->model->sftpConnectionInfo();
        $expected = [
            'username' => 'appserver.dev.abc',
            'host' => 'ssh.example.com',
            'port' => '2222',
            'password' => 'Use your account password',
            'url' => 'sftp://appserver.dev.abc@ssh.example.com:2222',
            'command' => 'sftp -o Port=2222 appserver.dev.abc@ssh.example.com',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function testWake()
    {
        $domain = $this->getMockBuilder(Domain::class)
            ->disableOriginalConstructor()
            ->getMock();
        $domain->id = 'domain.ext';
        $response = [
            'status_code' => 200,
            'headers'=> ['X-Pantheon-Styx-Hostname' => 'styx domain',]
        ];
        $expected = [
            'success' => true,
            'styx' => $response['headers']['X-Pantheon-Styx-Hostname'],
            'response' => $response,
            'target' => $domain->id,
        ];

        $this->domains->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$domain,]);
        $domain->expects($this->once())
            ->method('get')
            ->with('dns_zone_name')
            ->willReturn('anything but null');

        $this->request->expects($this->once())
            ->method('request')
            ->with($this->equalTo("http://{$domain->id}/pantheon_healthcheck"))
            ->willReturn($response);

        $out = $this->model->wake();
        $this->assertEquals($expected, $out);
    }

    public function testWipe()
    {
        $this->setUpWorkflowOperationTest(
            'wipe',
            [],
            'wipe'
        );
    }

    protected function createModel($params = ['id' => 'dev',])
    {
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->id = 'abc';
        $this->site->method('getName')->willReturn($this->site->id);

        $environments = new Environments(['site' => $this->site,]);
        $model = new Environment((object)$params, ['collection' => $environments,]);

        $this->container = new Container();

        $this->backups = $this->getMockBuilder(Backups::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->binding = $this->getMockBuilder(Binding::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->bindings = $this->getMockBuilder(Bindings::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->commits = $this->getMockBuilder(Commits::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->domains = $this->getMockBuilder(Domains::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->local_machine = $this->getMockBuilder(LocalMachineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->lock = $this->getMockBuilder(Lock::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->upstream_status = $this->getMockBuilder(UpstreamStatus::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->add(Backups::class, $this->backups);
        $this->container->add(Bindings::class, $this->bindings);
        $this->container->add(Commits::class, $this->commits);
        $this->container->add(Domains::class, $this->domains);
        $this->container->add(LocalMachineHelper::class, $this->local_machine);
        $this->container->add(Lock::class, $this->lock);
        $this->container->add(UpstreamStatus::class, $this->upstream_status);
        $this->container->add(Workflow::class, $this->workflow);
        $this->container->add(Workflows::class, $this->workflows);

        $model->setContainer($this->container);
        $model->setRequest($this->request);
        $model->setConfig($this->config);

        return $model;
    }

    protected function getCommits($commit_labels)
    {
        $commits = $this->getMockBuilder(Commits::class)
            ->disableOriginalConstructor()
            ->getMock();

        $commits_array = [];
        foreach ($commit_labels as $labels) {
            $commit = $this->getMockBuilder(Commit::class)
                ->disableOriginalConstructor()
                ->getMock();
            $commit->method('get')->with('labels')->willReturn($labels);
            $commits_array[] = $commit;
        }
        $commits->method('all')
            ->willReturn($commits_array);

        return $commits;
    }

    protected function getModelWithCommits($commit_labels)
    {
        $model = $this->createModel(['id' => 'live',]);
        $environments = $this->getMockBuilder(Environments::class)
            ->disableOriginalConstructor()
            ->getMock();

        $env = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $env->id = 'test';
        $environments->method('get')
            ->with('test')
            ->willReturn($env);

        $this->site->method('getEnvironments')->willReturn($environments);

        $env->method('getCommits')->willReturn($this->getCommits($commit_labels));
        return $model;
    }

    protected function setUpTestGetParentEnvironment($id, $parent)
    {
        $model = $this->createModel(['id' => $id,]);
        $environments = $this->getMockBuilder(Environments::class)
            ->disableOriginalConstructor()
            ->getMock();

        $env = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $environments->expects($this->once())
            ->method('get')
            ->with($parent)
            ->willReturn($env);

        $this->site->method('getEnvironments')->willReturn($environments);

        $this->assertEquals($env, $model->getParentEnvironment());
    }

    protected function setUpWorkflowOperationTest(
        $method,
        $method_params,
        $wf_name,
        $wf_params = null,
        $model_params = ['id' => 'dev',]
    ) {
        $model = $this->createModel($model_params);

        if ($wf_params) {
            $this->workflows->expects($this->any())
                ->method('create')
                ->with($wf_name, ['params' => $wf_params,])
                ->willReturn($this->workflow);
        } else {
            $this->workflows->expects($this->any())
                ->method('create')
                ->with($wf_name)
                ->willReturn($this->workflow);
        }

        $wf = call_user_func_array([$model, $method,], $method_params);
        $this->assertEquals($this->workflow, $wf);
    }
}
