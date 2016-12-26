<?php

namespace Pantheon\Terminus\UnitTests\Models;

use League\Container\Container;
use Pantheon\Terminus\Collections\Environments;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Lock;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Collections\Commits;
use Pantheon\Terminus\Models\Commit;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class EnvironmentTest
 * Testing class for Pantheon\Terminus\Models\Environment
 * @package Pantheon\Terminus\UnitTests\Models
 */
class EnvironmentTest extends ModelTestCase
{
    /**
     * @var Workflow
     */
    protected $workflow;
    /**
     * @var Workflows
     */
    protected $workflows;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->model = $this->_createModel(['id' => 'dev']);
    }

    protected function _createModel($params = ['id' => 'dev'])
    {
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->id = "abc";
        $this->site->method('getName')->willReturn('abc');

        $environments = new Environments(['site' => $this->site]);
        $model = new Environment((object)$params, ['collection' => $environments]);

        $this->container = new Container();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->lock = $this->getMockBuilder(Lock::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->local_machine = $this->getMockBuilder(LocalMachineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->add(Workflows::class, $this->workflows);
        $this->container->add(Lock::class, $this->lock);
        $this->container->add(LocalMachineHelper::class, $this->local_machine);


        $model->setContainer($this->container);
        $model->setRequest($this->request);
        $model->setConfig($this->config);

        return $model;
    }

    protected function _testWorkflowOperation(
        $method,
        $method_params,
        $wf_name,
        $wf_params = null,
        $model_params = ['id' => 'dev']
    ) {
        $model = $this->_createModel($model_params);

        if ($wf_params) {
            $this->workflows->expects($this->any())
                ->method('create')
                ->with($wf_name, ['params' => $wf_params])
                ->willReturn($this->workflow);
        } else {
            $this->workflows->expects($this->any())
                ->method('create')
                ->with($wf_name)
                ->willReturn($this->workflow);
        }

        $wf = call_user_func_array([$model, $method], $method_params);
        $this->assertEquals($this->workflow, $wf);
    }

    public function testApplyUpstreamUpdates()
    {
        $this->_testWorkflowOperation(
            'applyUpstreamUpdates',
            [],
            'apply_upstream_updates',
            ['updatedb' => true, 'xoption' => false]
        );
        $this->_testWorkflowOperation(
            'applyUpstreamUpdates',
            [false, true],
            'apply_upstream_updates',
            ['updatedb' => false, 'xoption' => true]
        );
    }

    public function testCacheserverConnectionInfo()
    {
        // @TODO: Test this once bindings are mockable
    }

    public function testChangeConnectionMode()
    {
        $this->_testWorkflowOperation(
            'changeConnectionMode',
            ['git'],
            'enable_git_mode',
            null,
            ['id' => 'dev', 'on_server_development' => true]
        );

        $this->_testWorkflowOperation(
            'changeConnectionMode',
            ['sftp'],
            'enable_on_server_development',
            null,
            ['id' => 'dev']
        );

        $model = $this->_createModel(['id' => 'dev', 'on_server_development' => true]);
        $return = $model->changeConnectionMode('sftp');
        $this->assertEquals('The connection mode is already set to sftp.', $return);

        $model = $this->_createModel(['id' => 'dev']);
        $return = $model->changeConnectionMode('git');
        $this->assertEquals('The connection mode is already set to git.', $return);
    }

    public function testClearCache()
    {
        $this->_testWorkflowOperation(
            'clearCache',
            [],
            'clear_cache',
            ['framework_cache' => true,]
        );
    }

    public function testCloneDatabase()
    {
        $this->_testWorkflowOperation(
            'cloneDatabase',
            ['stage'],
            'clone_database',
            ['from_environment' => 'stage',]
        );
        $this->_testWorkflowOperation(
            'cloneDatabase',
            ['prod'],
            'clone_database',
            ['from_environment' => 'prod',]
        );
    }

    public function testCloneFiles()
    {
        $this->_testWorkflowOperation(
            'cloneFiles',
            ['stage'],
            'clone_files',
            ['from_environment' => 'stage',]
        );
        $this->_testWorkflowOperation(
            'cloneFiles',
            ['prod'],
            'clone_files',
            ['from_environment' => 'prod',]
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
            ->willReturn('dev@example.com');

        $this->local_machine->expects($this->at(1))
            ->method('exec')
            ->with('git config user.name')
            ->willReturn('Dev Tester');

        $actual = $this->model->commitChanges('Hello, World!');
        $this->assertEquals($this->workflow, $actual);
    }

    public function testConnectionInfo()
    {
        // @TODO: Test this when it's dependencies are testable
    }

    public function testConvergeBindings()
    {
        $this->_testWorkflowOperation(
            'convergeBindings',
            [],
            'converge_environment'
        );
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
        // @TODO: Test this when bingings are mockable
    }

    public function testDelete()
    {
        $model = $this->_createModel(['id' => 'mymulti']);
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
        $model = $this->_createModel(['id' => 'mymulti2']);
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
        $this->_testWorkflowOperation(
            'deploy',
            [['a' => '123', 'b' => '345']],
            'deploy',
            ['a' => '123', 'b' => '345']
        );
    }

    public function testDiffstat()
    {
        $expected = ['foo' => 'bar'];
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                'sites/abc/environments/dev/on-server-development/diffstat',
                ['method' => 'get']
            )
            ->willReturn(['data' => $expected]);
        $actual = $this->model->diffStat();
        $this->assertEquals($expected, $actual);
    }

    public function testDomain()
    {
        $model = $this->_createModel(['id' => 'dev', 'dns_zone' => 'example.com']);
        $expected = "dev-abc.example.com";
        $actual = $model->domain();
        $this->assertEquals($expected, $actual);
    }

    public function testGetDrushVersion()
    {
        $expected = '8.0';
        $this->request->expects($this->once())
            ->method('request')
            ->with(
                'sites/abc/environments/dev/settings',
                ['method' => 'get']
            )
            ->willReturn(['data' => (object)['drush_version' => $expected]]);
        $actual = $this->model->getDrushVersion();
        $this->assertEquals($expected, $actual);
    }

    public function testGetLock()
    {
        // @TODO: Test this when Lock uses the di container
    }

    public function testGetName()
    {
        $actual = $this->model->getName();
        $this->assertEquals('dev', $actual);
    }

    protected function _testGetParentEnvironment($id, $parent)
    {
        $model = $this->_createModel(['id' => $id]);
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

    public function testGetParentEnvironmentDev()
    {
        $this->assertNull($this->model->getParentEnvironment());
    }

    public function testGetParentEnvironmentTest()
    {
        $this->_testGetParentEnvironment('test', 'dev');
    }

    public function testGetParentEnvironmentLive()
    {
        $this->_testGetParentEnvironment('live', 'test');
    }

    public function testGetParentEnvironmentMultiDev()
    {
        $this->_testGetParentEnvironment('mymulti', 'dev');
    }

    public function testGitConnectionInfo()
    {
        $actual = $this->model->gitConnectionInfo();
        $expected = [
            'username' => 'codeserver.dev.abc',
            'host' => 'codeserver.dev.abc.drush.in',
            'port' => '2222',
            'url' => 'ssh://codeserver.dev.abc@codeserver.dev.abc.drush.in:2222/~/repository.git',
            'command' => 'git clone ssh://codeserver.dev.abc@codeserver.dev.abc.drush.in:2222/~/repository.git ',
        ];
        $this->assertEquals($expected, $actual);
    }

    public function _getCommits($commit_labels)
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

    public function _getModelWithCommits($commit_labels)
    {
        $model = $this->_createModel(['id' => 'live']);
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

        $env->method('getCommits')->willReturn($this->_getCommits($commit_labels));
        return $model;
    }

    public function testCountDeployableCode()
    {
        $model = $this->_getModelWithCommits([]);
        $this->assertFalse($model->hasDeployableCode());
        $model = $this->_getModelWithCommits([]);
        $this->assertEquals(0, $model->hasDeployableCode());

        $commits = [
            ['not', 'deployable'],
            ['also', 'not', 'deployable'],
            ['live', 'not', 'deployable'],
            ['dev', 'not', 'deployable'],
        ];
        $model = $this->_getModelWithCommits($commits);
        $this->assertFalse($model->hasDeployableCode());
        $model = $this->_getModelWithCommits($commits);
        $this->assertEquals(0, $model->hasDeployableCode());

        $commits = [
            ['not', 'deployable'],
            ['also', 'not', 'deployable'],
            ['not-deployable', 'live', 'test'],
            ['also', 'deployable', 'test'],
        ];
        $model = $this->_getModelWithCommits($commits);
        $this->assertTrue($model->hasDeployableCode());
        $model = $this->_getModelWithCommits($commits);
        $this->assertEquals(1, $model->hasDeployableCode());

        $commits = [
            ['not', 'deployable'],
            ['also', 'not', 'deployable'],
            ['deployable', 'test'],
            ['also', 'deployable', 'test'],
        ];
        $model = $this->_getModelWithCommits($commits);
        $this->assertTrue($model->hasDeployableCode());
        $model = $this->_getModelWithCommits($commits);
        $this->assertEquals(2, $model->hasDeployableCode());
    }

    public function testImportDatabase()
    {
        $this->_testWorkflowOperation(
            'importDatabase',
            ['https://example.com/myfile.sql'],
            'import_database',
            ['url' => 'https://example.com/myfile.sql']
        );
    }

    public function testImport()
    {
        $this->_testWorkflowOperation(
            'import',
            ['https://example.com/myfile.tar.gz'],
            'do_migration',
            ['url' => 'https://example.com/myfile.tar.gz']
        );
    }

    public function testImportFiles()
    {
        $this->_testWorkflowOperation(
            'importFiles',
            ['https://example.com/myfile.tar.gz'],
            'import_files',
            ['url' => 'https://example.com/myfile.tar.gz']
        );
    }

    public function testInitializeBindings()
    {
        $this->_testWorkflowOperation(
            'initializeBindings',
            [],
            'create_environment',
            [
                'annotation' => 'Create the test environment',
                'clone_database' => ['from_environment' => 'dev',],
                'clone_files' => ['from_environment' => 'dev',],
            ],
            ['id' => 'test']
        );
        $this->_testWorkflowOperation(
            'initializeBindings',
            [],
            'create_environment',
            [
                'annotation' => 'Create the live environment',
                'clone_database' => ['from_environment' => 'test',],
                'clone_files' => ['from_environment' => 'test',],
            ],
            ['id' => 'live']
        );
    }

    public function testEnvIsInitialized()
    {
        $this->assertTrue($this->model->isInitialized());

        $model = $this->_createModel(['id' => 'mymulti']);
        $this->assertTrue($model->isInitialized());

        $commits = [
            ['some', 'commit'],
        ];
        $model = $this->_createModel(['id' => 'test']);
        $model->commits = $this->_getCommits($commits);
        $this->assertTrue($model->isInitialized());

        $commits = [];
        $model = $this->_createModel(['id' => 'test']);
        $model->commits = $this->_getCommits($commits);
        $this->assertFalse($model->isInitialized());
    }

    public function testIsMultidev()
    {
        $this->assertFalse($this->model->isMultidev());

        $model = $this->_createModel(['id' => 'test']);
        $this->assertFalse($model->isMultidev());

        $model = $this->_createModel(['id' => 'live']);
        $this->assertFalse($model->isMultidev());

        $model = $this->_createModel(['id' => 'mymulti']);
        $this->assertTrue($model->isMultidev());
    }

    public function testMergeFromDev()
    {
        $this->_testWorkflowOperation(
            'mergeFromDev',
            [],
            'merge_dev_into_cloud_development_environment',
            ['updatedb' => false],
            ['id' => 'mymulti']
        );

        $this->_testWorkflowOperation(
            'mergeFromDev',
            [['updatedb' => true]],
            'merge_dev_into_cloud_development_environment',
            ['updatedb' => true],
            ['id' => 'mymulti']
        );

        $this->setExpectedException(TerminusException::class, 'The dev environment is not a multidev environment');
        $model = $this->_createModel(['id' => 'dev']);
        $model->mergeFromDev();
    }

    public function testMergeToDev()
    {

        $this->_testWorkflowOperation(
            'mergeToDev',
            [],
            'merge_cloud_development_environment_into_dev',
            ['updatedb' => false, 'from_environment' => null]
        );

        $this->_testWorkflowOperation(
            'mergeToDev',
            [['updatedb' => true, 'from_environment' => 'mymulti']],
            'merge_cloud_development_environment_into_dev',
            ['updatedb' => true, 'from_environment' => 'mymulti']
        );

        $this->setExpectedException(
            TerminusException::class,
            'Environment::mergeToDev() may only be run on the dev environment.'
        );
        $model = $this->_createModel(['id' => 'stage']);
        $model->mergeToDev();
    }

    public function testSendCommandViaSsh()
    {
        $expected = ['output' => 'Hello, World!', 'exit_code' => 0];
        $this->local_machine->expects($this->at(0))
            ->method('execInteractive')
            ->with('ssh -T dev.abc@appserver.dev.abc.drush.in -p 2222 -o "AddressFamily inet" \'echo "Hello, World!"\'')
            ->willReturn($expected);

        $actual = $this->model->sendCommandViaSsh('echo "Hello, World!"');
        $this->assertEquals($expected, $actual);

        $this->configSet(['test_mode' => 1]);
        $expected = [
            'output' => "Terminus is in test mode. "
                . "Environment::sendCommandViaSsh commands will not be sent over the wire. "
                . "SSH Command: ssh -T dev.abc@appserver.dev.abc.drush.in -p 2222 -o \"AddressFamily inet\" 'echo \"Hello, World!\"'",
            'exit_code' => 0
        ];
        $actual = $this->model->sendCommandViaSsh('echo "Hello, World!"');
        $this->assertEquals($expected, $actual);
    }

    public function testSerialize()
    {
        $this->config->method('get')->with('date_format')->willReturn('Y-m-d');
        $this->configSet(['date_format' => 'Y-m-d']);

        $info = [
            'id' => 'dev',
            'environment_created' => '1479413982',
            'on_server_development' => true,
            'php_version' => '70',
            'dns_zone' => 'example.com'
        ];
        $this->lock->method('isLocked')->willReturn(false);
        $model = $this->_createModel($info);
        $actual = $model->serialize();
        $expected = [
            'id' => 'dev',
            'created' => '2016-11-17',
            'domain' => 'dev-abc.example.com',
            'onserverdev' => 'true',
            'locked' => 'false',
            'initialized' => 'true',
            'connection_mode' => 'sftp',
            'php_version' => '7.0',
        ];
        $this->assertEquals($expected, $actual);

        $info['on_server_development'] = false;
        $expected['onserverdev'] = 'false';
        $expected['connection_mode'] = 'git';
        $model = $this->_createModel($info);
        $actual = $model->serialize();
        $this->assertEquals($expected, $actual);
    }

    public function testSetHttpsCertificate()
    {
        // @TODO: This needs to be refactored before it can be tested
    }

    public function testDisableHttpsCertificate()
    {
        $this->request->expects($this->at(0))
            ->method('request')
            ->with(
                'sites/abc/environments/dev/settings',
                ['method' => 'get']
            )
            ->willReturn(['data' => (object)['ssl_enabled' => true]]);

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
            ->willReturn(['data' => "Ok"]);

        $this->model->disableHttpsCertificate();
    }

    public function testDisableHttpsCertificateFailed()
    {
        $this->request->expects($this->at(0))
            ->method('request')
            ->with(
                'sites/abc/environments/dev/settings',
                ['method' => 'get']
            )
            ->willReturn(['data' => (object)['ssl_enabled' => true]]);

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
            ->willReturn(['data' => (object)['ssl_enabled' => false]]);

        $this->setExpectedException(TerminusException::class, 'The dev environment does not have https enabled.');
        $this->model->disableHttpsCertificate();
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
        $this->configSet(['host' => 'onebox', 'ssh_host' => null]);
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
        $this->configSet(['ssh_host' => 'ssh.example.com']);
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
        // @TODO: Test this when hostnames is testable
    }

    public function testWipe()
    {
        $this->_testWorkflowOperation(
            'wipe',
            [],
            'wipe'
        );
    }
}
