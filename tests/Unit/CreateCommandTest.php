<?php

namespace Pantheon\Terminus\Tests\Unit;

use Consolidation\Config\Config;
use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Commands\Site\CreateCommand;
use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\VcsApi\Client as VcsClient;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;

/**
 * Sentinel exception used to abort method execution after the workflow params
 * are captured, so tests don't need to stub out the full post-creation chain.
 */
class WorkflowParamsCaptured extends RuntimeException
{
    public array $params;

    public function __construct(array $params)
    {
        $this->params = $params;
        parent::__construct('Params captured');
    }
}

/**
 * Test site creation validation in CreateCommand.
 */
class CreateCommandTest extends TestCase
{
    private function makeUpstream(): Upstream
    {
        $upstream = $this->createMock(Upstream::class);
        $upstream->id = 'upstream-uuid';
        $upstream->method('get')->willReturnCallback(fn(string $k) => match ($k) {
            'framework' => 'drupal',
            'label'     => 'Test Upstream',
            default     => null,
        });
        return $upstream;
    }

    /**
     * Builds a testable CreateCommand subclass for the Pantheon-hosted path.
     *
     * Sites::create() is mocked to capture its $params argument, then throw
     * WorkflowParamsCaptured to abort further execution.
     */
    private function makePantheonHostedCommand(): CreateCommand
    {
        $config = new Config();

        $sites = $this->createMock(Sites::class);
        $sites->method('create')->willReturnCallback(function (array $params) {
            throw new WorkflowParamsCaptured($params);
        });

        return new class ($sites, $config) extends CreateCommand {
            private Sites $mockSites;

            public function __construct(Sites $sites, Config $config)
            {
                $this->mockSites = $sites;
                $this->config = $config;
            }

            public function sites(): Sites
            {
                return $this->mockSites;
            }

            public function log(): \Psr\Log\LoggerInterface
            {
                return new NullLogger();
            }

            public function callCreatePantheonHostedSite(
                string $site_name,
                string $label,
                Upstream $upstream,
                User $user,
                array $options
            ): array {
                try {
                    $this->createPantheonHostedSite($site_name, $label, $upstream, $user, $options);
                } catch (WorkflowParamsCaptured $e) {
                    return $e->params;
                }
                return [];
            }
        };
    }

    /**
     * Builds a testable CreateCommand subclass for the EVCS path.
     *
     * Workflows::create() is mocked to capture its 'params' option into $capturedParams,
     * then return a Workflow mock whose get('waiting_for_task') throws WorkflowParamsCaptured
     * to abort further execution before any post-creation work is attempted.
     */
    private function makeEvcsCommand(array &$capturedParams): CreateCommand
    {
        $config = new Config();

        $waitingForTask = (object) ['params' => (object) ['site_id' => null]];

        $workflow = $this->createMock(Workflow::class);
        $workflow->id = 'wf-123';
        $workflow->method('get')->willReturnCallback(function (string $key) use (&$capturedParams) {
            if ($key === 'waiting_for_task') {
                // Abort execution once we've captured params — site_id is null,
                // which triggers an exception check in createExternallyHostedSiteViaWorkflow.
                return null;
            }
            return null;
        });

        $workflows = $this->createMock(Workflows::class);
        $workflows->method('create')->willReturnCallback(
            function (string $type, array $options) use ($workflow, &$capturedParams) {
                $capturedParams = $options['params'] ?? [];
                return $workflow;
            }
        );

        $vcsClient = $this->createMock(VcsClient::class);

        return new class ($workflows, $vcsClient, $config) extends CreateCommand {
            private Workflows $mockWorkflows;
            private VcsClient $mockVcsClient;

            public function __construct(Workflows $workflows, VcsClient $vcsClient, Config $config)
            {
                $this->mockWorkflows = $workflows;
                $this->mockVcsClient = $vcsClient;
                $this->config = $config;
            }

            public function log(): \Psr\Log\LoggerInterface
            {
                return new NullLogger();
            }

            public function getVcsClient(): VcsClient
            {
                return $this->mockVcsClient;
            }

            public function callCreateExternallyHostedSiteViaWorkflow(
                string $site_name,
                string $label,
                Upstream $upstream,
                User $user,
                array $options,
                object $pantheon_org,
                string $installation_id,
                string $repo_name,
                bool $create_repo,
                string $vcs_provider,
                string $preferred_platform
            ): void {
                $workflows = $this->mockWorkflows;

                // Minimal User stub that returns our capturing Workflows mock.
                $userStub = new class ($workflows) extends User {
                    private Workflows $wf;
                    public function __construct(Workflows $wf) { $this->wf = $wf; }
                    public function getWorkflows(): Workflows { return $this->wf; }
                };

                // The method throws TerminusException when site_id is null (expected in unit tests).
                // We swallow it here — $capturedParams is already set by the Workflows mock.
                try {
                    $this->createExternallyHostedSiteViaWorkflow(
                        $site_name, $label, $upstream, $userStub, $options,
                        $pantheon_org, $installation_id, $repo_name,
                        $create_repo, $vcs_provider, $preferred_platform
                    );
                } catch (\Pantheon\Terminus\Exceptions\TerminusException $e) {
                    // Site ID not found — expected since Workflows::create() returns a stub
                    // that cannot provide a real site_id. Params were captured before this point.
                }
            }
        };
    }

    // -------------------------------------------------------------------------
    // Pantheon-hosted path
    // -------------------------------------------------------------------------

    public function testDatabaseRuntimeIncludedInPantheonHostedWorkflowParams(): void
    {
        $command = $this->makePantheonHostedCommand();
        $user = $this->createMock(User::class);

        $params = $command->callCreatePantheonHostedSite(
            'mysite',
            'My Site',
            $this->makeUpstream(),
            $user,
            ['org' => null, 'region' => null, 'database-runtime' => 'cloud_native_runtime_mapper']
        );

        $this->assertArrayHasKey('database_runtime', $params);
        $this->assertSame('cloud_native_runtime_mapper', $params['database_runtime']);
    }

    public function testDatabaseRuntimeAbsentWhenFlagNotProvidedOnPantheonHostedPath(): void
    {
        $command = $this->makePantheonHostedCommand();
        $user = $this->createMock(User::class);

        $params = $command->callCreatePantheonHostedSite(
            'mysite',
            'My Site',
            $this->makeUpstream(),
            $user,
            ['org' => null, 'region' => null, 'database-runtime' => null]
        );

        $this->assertArrayNotHasKey('database_runtime', $params);
    }

    // -------------------------------------------------------------------------
    // EVCS path
    // -------------------------------------------------------------------------

    public function testDatabaseRuntimeIncludedInEvcsWorkflowParams(): void
    {
        $capturedParams = [];
        $command = $this->makeEvcsCommand($capturedParams);
        $user = $this->createMock(User::class);
        $pantheon_org = (object) ['id' => 'org-uuid'];

        $command->callCreateExternallyHostedSiteViaWorkflow(
            'mysite', 'My Site', $this->makeUpstream(), $user,
            [
                'org'              => 'org-uuid',
                'region'           => null,
                'visibility'       => 'private',
                'create-repo'      => true,
                'skip-clone-repo'  => true,
                'database-runtime' => 'cloud_native_runtime_mapper',
            ],
            $pantheon_org, 'install-123', 'mysite', true, 'github', 'cos'
        );

        $this->assertArrayHasKey('database_runtime', $capturedParams);
        $this->assertSame('cloud_native_runtime_mapper', $capturedParams['database_runtime']);
    }

    public function testDatabaseRuntimeAbsentWhenFlagNotProvidedOnEvcsPath(): void
    {
        $capturedParams = [];
        $command = $this->makeEvcsCommand($capturedParams);
        $user = $this->createMock(User::class);
        $pantheon_org = (object) ['id' => 'org-uuid'];

        $command->callCreateExternallyHostedSiteViaWorkflow(
            'mysite', 'My Site', $this->makeUpstream(), $user,
            [
                'org'              => 'org-uuid',
                'region'           => null,
                'visibility'       => 'private',
                'create-repo'      => true,
                'skip-clone-repo'  => true,
                'database-runtime' => null,
            ],
            $pantheon_org, 'install-123', 'mysite', true, 'github', 'cos'
        );

        $this->assertArrayNotHasKey('database_runtime', $capturedParams);
    }
}
