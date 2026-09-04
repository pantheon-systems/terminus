<?php

namespace Pantheon\Terminus\Commands\Site;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Helpers\Traits\WaitForWakeTrait;
use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\VcsApi\Installation;
use Pantheon\Terminus\VcsApi\VcsClientAwareTrait;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Commands\Site\SiteCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Consolidation\AnnotatedCommand\AnnotationData;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Pantheon\Terminus\Traits\GithubInstallTrait;
use Pantheon\Terminus\Traits\BuildPathTrait;

/**
 * Creates a new site, potentially with an external Git repository.
 * This command overrides the core site:create command when the plugin is enabled.
 */
class CreateCommand extends SiteCommand implements RequestAwareInterface, SiteAwareInterface
{
    use WaitForWakeTrait;
    use WorkflowProcessingTrait;
    use VcsClientAwareTrait;
    use GithubInstallTrait;
    use BuildPathTrait;

    // Wait time for GitHub app installation to succeed.
    protected const AUTH_LINK_TIMEOUT = 600;
    protected const ADD_NEW_ORG_TEXT = 'Add to a new org';
    protected const REDIRECT_URL = 'https://docs.pantheon.io/github-application';

    // Default timeout.
    protected const DEFAULT_TIMEOUT = 600;

    // Supported VCS types (can be expanded later)
    protected $vcs_providers = ['pantheon', 'github', 'gitlab', 'bitbucket'];

    protected Process $serverProcess;

    /**
     * Creates a new site
     *
     * @authorize
     * @interact
     *
     * @command site:create
     * @aliases site-create
     *
     * @param string $site_name Site name (machine name)
     * @param string $label Site label (human-readable name)
     * @param string $upstream_id Upstream name or UUID (e.g., wordpress, drupal-composer-managed)
     * @option org Organization name, label, or ID (required).
     * @option region Specify the service region where the site should be created. See documentation for valid regions.
     * @option vcs-provider VCS provider for the site repository (e.g., github, pantheon). Default is pantheon.
     * @option vcs-org Name of the Github organization containing the repository. Required if --vcs-provider=github is used.
     * @option visibility Visibility of the external repository (private or public). Only applies if --vcs-provider=github. Default is private.
     * @option vcs-token Personal access token for the VCS provider. Only applies if --vcs-provider=gitlab.
     * @option create-repo Whether to create a repository in the VCS provider. Default is true.
     * @option repository-name Name of the repository to create in the VCS provider. Only applies if --vcs-provider is not Pantheon.
     * @option skip-clone-repo Do not clone the repository after creation. Default is false.
     * @option vcs-host Hostname of a GitHub Enterprise Server instance (e.g., ghes.example.com). Only valid with --vcs-provider=github. Must be registered via vcs:github-host:add first.
     * @option build-path Relative path within the repository to the buildable app (e.g., apps/web). For monorepos. Only valid with an external VCS provider. Defaults to the repository root.
     *
     * @usage <site> <label> <upstream> Creates a new Pantheon-hosted site named <site>, labeled <label>, using code from <upstream>.
     * @usage <site> <label> <upstream> --org=<org> Creates site associated with <organization>, with a Pantheon-hosted git repository.
     * @usage <site> <label> <upstream> --org=<org> --vcs-provider=github --vcs-org=<github-org> Creates a new site associated with Pantheon <organization>, using <upstream> code, with the repository hosted on Github in the <github-org> organization.
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Exception
     */
    public function create(
        $site_name,
        $label,
        $upstream_id,
        $options = [
            'org' => null,
            'region' => null,
            'vcs-provider' => 'pantheon',
            'vcs-org' => null,
            'visibility' => 'private',
            'create-repo' => true,
            'repository-name' => null,
            'skip-clone-repo' => false,
            'vcs-host' => null,
            'build-path' => null,
            'vcs-token' => null,
        ]
    ) {
        $vcs_provider = strtolower($options['vcs-provider']);
        $org_id = $options['org'];

        if (!empty($options['vcs-host']) && !in_array($vcs_provider, ['github', 'gitlab'])) {
            throw new TerminusException(
                'The --vcs-host option is only valid with --vcs-provider=github or --vcs-provider=gitlab.'
            );
        }

        // Validate VCS provider
        if (!in_array($vcs_provider, $this->vcs_providers)) {
            throw new TerminusException(
                'Invalid VCS provider specified: {vcs-provider}. Supported providers are: {supported}',
                ['vcs-provider' => $vcs_provider, 'supported' => implode(', ', $this->vcs_providers)]
            );
        }

        // Validate site name uniqueness
        if ($this->sites()->nameIsTaken($site_name)) {
            throw new TerminusException('The site name {site_name} is already taken.', compact('site_name'));
        }

        // Validate eVCS Pantheon org requirement
        if ($vcs_provider !== 'pantheon') {
            if (empty($org_id)) {
                throw new TerminusException(
                    'The --org option is required when using an external VCS provider (--vcs-provider={vcs}).',
                    ['vcs-provider' => $vcs_provider]
                );
            }
        } else {
            // If using Pantheon, no create-repo is not supported.
            if (!$options['create-repo']) {
                throw new TerminusException(
                    'The --no-create-repo option is not supported when using Pantheon as the VCS provider.'
                );
            }
            if ($options['repository-name']) {
                throw new TerminusException(
                    'The --repository-name option is not supported when using Pantheon as the VCS provider.'
                );
            }
            if (!empty($options['build-path'])) {
                throw new TerminusException(
                    'The --build-path option is not supported when using Pantheon as the VCS provider.'
                );
            }
        }

        // Validate the build path format (relative subdir, no traversal).
        if (!empty($options['build-path'])) {
            $build_path_error = self::validateBuildPath($options['build-path']);
            if ($build_path_error !== null) {
                throw new TerminusException(
                    'Invalid --build-path: {error}',
                    ['error' => $build_path_error]
                );
            }
        }

        // Get User and Upstream
        $user = $this->session()->getUser();
        $upstream = $this->getValidatedUpstream($user, $upstream_id);

        // Validate Node.js sites cannot use Pantheon-hosted repositories
        if ($upstream->get('framework') === 'nodejs' && $vcs_provider === 'pantheon') {
            throw new TerminusException(
                'Node.js sites cannot be created with Pantheon-hosted repositories.'
                    . ' Please specify an external VCS provider using --vcs-provider'
                    . ' (e.g., --vcs-provider=github).'
            );
        }

        // Branch based on VCS provider
        if ($vcs_provider === 'pantheon') {
            $this->createPantheonHostedSite($site_name, $label, $upstream, $user, $options);
        } else {
            // For now, only github is supported other than pantheon
            $this->createExternallyHostedSite($site_name, $label, $upstream, $user, $options);
        }
    }

    public function __destruct()
    {
        if (isset($this->serverProcess) && $this->serverProcess->isRunning()) {
            $this->serverProcess->stop(0);
        }
    }

    /**
     * Prompts for required options depending on the ones that were passed.
     *
     * @hook post-interact site:create
     */
    public function promptForRequired(InputInterface $input, OutputInterface $output, AnnotationData $annotation_data)
    {
        $original_provider = $input->getOption('vcs-provider');
        if ($original_provider === 'pantheon') {
            $upstream_id = $input->getArgument('upstream_id');
            $user = $this->session()->getUser();
            $upstream = $this->getValidatedUpstream($user, $upstream_id);

            if ($upstream->get('framework') === 'nodejs') {
                $helper = new QuestionHelper();
                $question = new ChoiceQuestion(
                    'Node.js sites cannot be created with Pantheon-hosted repositories. Please select a VCS provider:',
                    [
                        'github' => 'GitHub',
                    ]
                );
                $question->setErrorMessage('Invalid selection.');
                $chosen_provider = $helper->ask($input, $output, $question);
                $input->setOption('vcs-provider', $chosen_provider);
            }
        }

        $vcs_provider = strtolower($input->getOption('vcs-provider'));
        $org_id = $input->getOption('org');

        // If the user didn't provide --org, prompt them for it.
        if ($vcs_provider !== 'pantheon' && empty($org_id)) {
            $organizations = [];
            $user = $this->session()->getUser();
            $orgs = $user->getOrganizationMemberships()->all();
            foreach ($orgs as $org) {
                $organization = $org->getOrganization();
                $organizations[$organization->id] = $organization->getLabel();
            }

            $helper = new QuestionHelper();
            $question = new ChoiceQuestion(
                'Please specify the Pantheon organization:',
                $organizations,
            );
            $question->setErrorMessage('Invalid selection.');
            $chosen_org = $helper->ask($input, $output, $question);
            $input->setOption('org', $chosen_org);
        }
    }


    /**
     * Validates the upstream ID and returns the Upstream object.
     * Provides a more helpful error message if the upstream isn't found.
     */
    protected function getValidatedUpstream(User $user, string $upstream_id): Upstream
    {
        try {
            // Use the user's upstream collection to find the upstream
            return $user->getUpstreams()->get($upstream_id);
        } catch (TerminusNotFoundException $e) {
            // Provide a more helpful error message if the upstream isn't found.
            $this->log()->error('Could not find upstream: {upstream}', ['upstream' => $upstream_id]);
            try {
                // Attempt to list available upstreams for better user feedback
                $available_upstreams = array_map(function ($up) {
                    return $up->id; // Or $up->get('label') for human-readable names
                }, $user->getUpstreams()->all());
                if (!empty($available_upstreams)) {
                    $this->log()->error('Available upstreams: {list}', ['list' => implode(', ', $available_upstreams)]);
                } else {
                    $this->log()->warning('Could not retrieve list of available upstreams.');
                }
            } catch (\Exception $list_error) {
                $this->log()->warning(
                    'Could not retrieve list of available upstreams: {msg}',
                    ['msg' => $list_error->getMessage()]
                );
            }
            // Throw the final exception indicating the specific upstream wasn't found
            throw new TerminusException('Invalid upstream "{upstream}" specified.', ['upstream' => $upstream_id]);
        }
    }


    /**
     * Handles creation of a standard Pantheon-hosted site.
     * (Logic adapted from core CreateCommand)
     */
    protected function createPantheonHostedSite($site_name, $label, Upstream $upstream, User $user, array $options)
    {
        $this->log()->notice('Creating a new Pantheon-hosted site...');

        $workflow_options = [
            'label' => $label,
            'site_name' => $site_name,
        ];

        $region = $options['region'] ?? $this->config->get('command_site_options_region');
        if ($region) {
            $workflow_options['preferred_zone'] = $region;
            $this->log()->notice('Attempting to create site in region: {region}', compact('region'));
        }

        $org = null;
        $org_id = $options['org'];
        if ($org_id === null) {
            throw new TerminusException(
                'Site creation requires an organization. Use the --org option to specify one. '
                . 'Example: terminus site:create {site_name} {label} {upstream_id} --org=<org-name>',
                compact('site_name', 'label', 'upstream_id')
            );
        }

        try {
            // It's better to get the membership first, then the organization
            $membership = $user->getOrganizationMemberships()->get($org_id);
            $org = $membership->getOrganization();
            $workflow_options['organization_id'] = $org->id;
            $this->log()->notice('Associating site with organization: {org_label} ({org_id})', [
                'org_label' => $org->get('profile')->name,
                'org_id' => $org->id,
            ]);
        } catch (TerminusNotFoundException $e) {
            throw new TerminusException(
                'Organization "{org}" not found or you are not a member.',
                ['org' => $org_id]
            );
        } catch (\Exception $e) {
            // Catch other potential errors during org fetching
            throw new TerminusException(
                'Error retrieving organization "{org}": {message}',
                ['org' => $org_id, 'message' => $e->getMessage()]
            );
        }

        // Create the site record via Pantheon API
        $this->log()->notice('Submitting site creation request to Pantheon API...');
        $workflow = $this->sites()->create($workflow_options);
        $this->processWorkflow($workflow);
        $this->log()->notice('Pantheon site record created successfully.');

        // Deploy the upstream CMS code
        $site_id = $workflow->get('waiting_for_task')->site_id ?? null;
        if (!$site_id) {
            throw new TerminusException('Could not get site ID from site creation workflow.');
        }

        if ($site = $this->getSiteById($site_id)) {
            $this->log()->notice('Deploying CMS ({upstream_label} - {upstream_id})...', [
                'upstream_label' => $upstream->get('label'),
                'upstream_id' => $upstream->id,
            ]);
            $this->processWorkflow($site->deployProduct($upstream->id));
            $this->log()->notice('CMS deployed successfully.');

            // Finally, wait for the dev environment to be ready.
            $this->waitForDevEnvironment($site, "cos");

            // Output final success message
            $this->log()->notice('---');
            $this->log()->notice('Site "{site}" created successfully!', ['site' => $site->getName()]);
            $dashboard_url = $site->dashboardUrl($org ? $org->id : null);
            $this->log()->notice('Pantheon Dashboard: {url}', ['url' => $dashboard_url]);
        } else {
            // This shouldn't happen if the create workflow succeeded and returned an ID, but good to handle.
            throw new TerminusException(
                'Failed to retrieve site object (ID: {id}) after creation workflow succeeded.',
                ['id' => $site_id]
            );
        }
    }

    /**
     * Wait for wake on the dev environment for a STA site.
     */
    protected function waitForWakeSta(Environment $env)
    {
        $domains = array_filter(
            $env->getDomains()->all(),
            function ($domain) {
                $domain_type = $domain->get('type');
                return (!empty($domain_type) && $domain_type == "platform");
            }
        );
        if (empty($domains)) {
            throw new TerminusException('No valid domains found for health check.');
        }
        $domain = array_pop($domains);
        $start_time = time();
        $polling_interval = $this->getConfig()->get('http_retry_delay_ms', 1000);
        do {
            usleep($polling_interval * 1000);
            $current_time = time();
            if (($current_time - $start_time) > self::DEFAULT_TIMEOUT) {
                throw new TerminusException('Timeout waiting for dev environment to become available.');
            }
            try {
                $response = $this->request()->request(
                    "https://{$domain->id}/",
                    [
                        'headers' => [
                            'Deterrence-Bypass' => 1,
                        ],
                    ],
                );
            } catch (TerminusException $e) {
                $this->log()->debug('Error while checking site status: {message}', ['message' => $e->getMessage()]);
                continue;
            }
            $success = $response->getStatusCode() === 200;
            if ($success) {
                $this->log()->notice('Site seems to be up and running.');
                break;
            }
        } while (true);
    }

    /**
     * Wait for dev environment to be ready to handle traffic.
     */
    protected function waitForDevEnvironment(Site $site, string $preferred_platform)
    {
        $this->log()->notice('Waiting for site dev environment to become available...');
        try {
            $env = $site->getEnvironments()->get('dev');
            if ($env) {
                if ($preferred_platform == "cos") {
                    $this->waitForWake($env, $this->logger);
                }
                if ($preferred_platform == "sta") {
                    $this->waitForWakeSta($env);
                }
                $this->log()->notice('Site dev environment is available.');
            } else {
                // This case should ideally not happen if the site exists
                $this->log()->warning(
                    'Could not retrieve the dev environment information, unable to confirm availability.'
                );
            }
        } catch (TerminusNotFoundException $e) {
            $this->log()->warning(
                'Dev environment not found immediately after site creation. It might still be provisioning.'
            );
            $this->log()->debug('TerminusNotFoundException: {message}', ['message' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->log()->error(
                'An error occurred while waiting for the site to wake: {message}',
                ['message' => $e->getMessage()]
            );
        }
    }

    /**
     * Handles creation of a site with an externally hosted repository (e.g., GitHub).
     */
    protected function createExternallyHostedSite($site_name, $label, Upstream $upstream, User $user, array $options)
    {
        $this->log()->notice(
            'Starting creation process for site with external VCS ({vcs-provider})...',
            ['vcs-provider' => $options['vcs-provider']]
        );

        $input = $this->input();
        $output = $this->output();
        $is_interactive = $input->isInteractive();
        $vcs_client = $this->getVcsClient();

        // Should be 'github/gitlab/bitbucket'.
        $vcs_provider = strtolower($options['vcs-provider']);
        $this->log()->debug('VCS provider: {vcs_provider}', ['vcs_provider' => $vcs_provider]);

        // Pantheon Org ID/Name/Label
        $org_id = $options['org'];
        $this->log()->debug('Pantheon organization ID: {org_id}', ['org_id' => $org_id]);

        $repo_name = $options['repository-name'] ?? $site_name;
        $create_repo = $options['create-repo'];

        $this->log()->debug('Repository name: {repo_name}', ['repo_name' => $repo_name]);


        // 1. Get Pantheon Organization.
        try {
            $membership = $user->getOrganizationMemberships()->get($org_id);
            $pantheon_org = $membership->getOrganization();
        } catch (TerminusNotFoundException $e) {
            // This should have been caught earlier, but double-check
            throw new TerminusException(
                'Pantheon organization "{org}" not found or you are not a member.',
                ['org' => $org_id]
            );
        }

        // 2. Determine Site Type & Platform
        $site_type = $this->getSiteType($upstream);
        $preferred_platform = $this->getPreferredPlatformForFramework($site_type);

        // 3. Get existing installations + link to create new installation.
        $installations_resp = $vcs_client->getInstallations($pantheon_org->id, $user->id);
        $existing_installations_data = $installations_resp['data'] ?? [];
        $this->log()->debug(
            'Existing installations: {installations}',
            ['installations' => print_r($existing_installations_data, true)]
        );

        list($url, $flag_file, $process) = $this->startTemporaryServer();
        // Store the process so we can stop it later.
        $this->serverProcess = $process;

        $github_host = $options['vcs-host'] ?? null;
        $auth_links_resp = $vcs_client->getAuthLinks($pantheon_org->id, $user->id, $site_type, $url, $github_host);
        $auth_links = $auth_links_resp['data'] ?? null;
        $this->log()->debug('VCS Auth Links: {auth_links}', ['auth_links' => print_r($auth_links, true)]);
        $auth_url = null;
        // Iterate over the two possible auth options for the given VCS.
        foreach (['app', 'oauth'] as $auth_option) {
            if (isset($auth_links->{sprintf("%s_%s", $vcs_provider, $auth_option)})) {
                $auth_url = sprintf('"%s"', $auth_links->{sprintf("%s_%s", $vcs_provider, $auth_option)});
                break;
            }
        }

        // GitHub requires an authorization URL.
        if ($vcs_provider === 'github' && (is_null($auth_url) || $auth_url === '""')) {
            throw new TerminusException(
                'Error authorizing with vcs service: {error_message}',
                ['error_message' => 'No vcs_auth_link returned']
            );
        }

        // 4. Figure out what installation to use and authorize (or create new installation).
        $vcs_org = $options['vcs-org'];

        // Installations is the Installation class objects array.
        $installations = [];
        // Installations_map is a map of lowercase login names to installation IDs.
        $installations_map = [];
        if (!empty($existing_installations_data)) {
            foreach ($existing_installations_data as $installation) {
                // Filter for current VCS provider and backend installations
                if (
                    strtolower($installation->alias) !== $vcs_provider
                    || $installation->installation_type == 'front-end'
                ) {
                    continue;
                }
                $installations[$installation->installation_id] = new Installation(
                    $installation->installation_id,
                    $installation->alias,
                    $installation->login_name,
                    $installation->hostname ?? null
                );
                $instKey = strtolower($installation->login_name);
                $installations_map[$instKey] = $installation->installation_id;
            }
        }
        $this->log()->debug(
            'Found {count} existing GitHub installations for Org {org}: {names}',
            [
                'count' => count($installations),
                'org' => $pantheon_org->id,
                'names' => implode(', ', array_keys($installations_map)),
            ]
        );

        $this->log()->debug('Installation map: {map}', ['map' => print_r($installations_map, true)]);
        $this->log()->debug(
            'Existing installations: {installations}',
            ['installations' => print_r($installations, true)]
        );

        $installation_id = null;

        // If vcs_org is provided, look it up in the installation map
        //   - If it matches an existing installation, use that; otherwise, assume the option was NOT provided
        // If vcs_org is NOT provided, present the user with a list of existing installations and the option for a new one.
        if ($vcs_org) {
            if ($github_host) {
                // Filter by both login name and hostname
                foreach ($installations as $id => $inst) {
                    if (strtolower($inst->getLoginName()) === $vcs_org && $inst->getHostname() === $github_host) {
                        $installation_id = $id;
                        $installation_human_name = $vcs_org;
                        break;
                    }
                }
            } elseif (isset($installations_map[$vcs_org])) {
                $installation_id = $installations_map[$vcs_org];
                $installation_human_name = $vcs_org;
            }
        }

        if (!$installation_id) {
            // We need to prompt the user for a installation; either because vcs_org was not provided or it didn't match an existing installation.
            if (!$is_interactive) {
                // Non-interactive mode, vcs_org not provided or not found
                throw new TerminusException(
                    '--vcs-org is required to match an existing installation'
                        . ' in non-interactive mode when --vcs-provider is not Pantheon.'
                );
            }
            if (!empty($installations)) {
                // Prompt user to choose from existing or add new
                $choices = [];
                foreach ($installations as $id => $inst) {
                    $hostname = $inst->getHostname();
                    $host_suffix = ($hostname !== 'github.com') ? sprintf(' @ %s', $hostname) : '';
                    $choices[$inst->getLoginName()] = sprintf(
                        "%s: %s (%s)%s",
                        $inst->getVendor(),
                        $inst->getLoginName(),
                        $id,
                        $host_suffix
                    );
                }
                $choices[self::ADD_NEW_ORG_TEXT] = 'new';

                $helper = new QuestionHelper();
                $question = new ChoiceQuestion(
                    'Which VCS organization should be used?',
                    array_keys($choices)
                );
                $question->setErrorMessage('Invalid selection %s.');
                $vcs_org_name = $helper->ask($input, $output, $question);

                $installation_id = $choices[$vcs_org_name];
                $installation_human_name = 'new';

                if ($installation_id !== 'new') {
                    $installation_human_name = $vcs_org_name;
                    $installation_id = $installations_map[strtolower($vcs_org_name)];
                }

                $this->log()->info(
                    'Selected to go with {installation} installation.',
                    ['installation' => $installation_human_name]
                );
            } else {
                // No existing installations found, prompt for new.
                $installation_id = 'new';
            }
        }

        // Ensure we have determined the installation ID and target org name
        if (is_null($installation_id)) {
            throw new TerminusException('Could not determine GitHub installation.');
        }

        $existing_installation = true;

        if ($installation_id === 'new') {
            $existing_installation = false;
            $success = $this->handleNewInstallation($vcs_provider, $auth_url, $flag_file, $options, $pantheon_org);
            if (!$success) {
                throw new TerminusException(
                    'Error authorizing with VCS service:'
                        . ' Timeout waiting for authorization to complete.'
                );
            }
            $installations_resp = $vcs_client->getInstallations($pantheon_org->id, $user->id);
            $new_existing_installations_data = $installations_resp['data'] ?? [];
            $this->log()->debug(
                'New existing installations: {installations}',
                ['installations' => print_r($new_existing_installations_data, true)]
            );
            // Look for a new installation that wasn't in the previous list.
            foreach ($new_existing_installations_data as $installation) {
                if (
                    strtolower($installation->alias) !== $vcs_provider
                    || $installation->installation_type == 'front-end'
                ) {
                    continue;
                }
                if (!isset($installations[$installation->installation_id])) {
                    $installation_id = $installation->installation_id;
                    $installation_human_name = $installation->login_name;
                    break;
                }
            }
        }

        // 5. Validate repository name and existence via VCS service.
        $validation = $vcs_client->validateRepositoryName(
            $repo_name,
            $pantheon_org->id,
            $installation_id,
            !$create_repo
        );
        if (!($validation['data']->valid ?? false)) {
            $errors = (array) ($validation['data']->errors ?? ['Invalid repository name.']);
            throw new TerminusException(implode(' ', $errors));
        }

        // 6. Use workflow for all sites
        $this->createExternallyHostedSiteViaWorkflow(
            $site_name,
            $label,
            $upstream,
            $user,
            $options,
            $pantheon_org,
            $installation_id,
            $repo_name,
            $create_repo,
            $vcs_provider,
            $preferred_platform
        );
    }

    /**
     * Creates a site with external VCS using the create_site workflow.
     */
    protected function createExternallyHostedSiteViaWorkflow(
        string $site_name,
        string $label,
        Upstream $upstream,
        User $user,
        array $options,
        $pantheon_org,
        string $installation_id,
        string $repo_name,
        bool $create_repo,
        string $vcs_provider,
        string $preferred_platform
    ) {
        $vcs_client = $this->getVcsClient();
        $this->log()->notice('Creating Pantheon site...');
        $vcs_id = array_search($vcs_provider, $this->vcs_providers);

        // Prepare workflow parameters
        $workflow_params = [
            'site_name' => $site_name,
            'organization_id' => $pantheon_org->id,
            'upstream_id' => $upstream->id,
            'evcs' => [
                'installation_id' => (string) $installation_id,
                'vendor_id' => (string) $vcs_id,
                'repo_name' => $repo_name,
                'skip_create' => !$create_repo,
                'is_private' => strtolower($options['visibility']) === 'private',
            ],
        ];

        // Forward the build path (monorepo subdir) to repository creation.
        // Omit when empty so the backend treats it as the repo root.
        if (!empty($options['build-path'])) {
            $workflow_params['evcs']['build_path'] = $options['build-path'];
        }

        // Add optional parameters
        if ($label) {
            $workflow_params['label'] = $label;
        }

        $region = $options['region'] ?? $this->config->get('command_site_options_region');
        if ($region) {
            $workflow_params['preferred_zone'] = $region;
            $this->log()->notice('Attempting to create site in region: {region}', compact('region'));
        }

        // Create the workflow
        try {
            $workflow = $user->getWorkflows()->create('create_site', [
                'params' => $workflow_params,
            ]);
            $this->log()->notice('Site creation workflow initiated (ID: {id}).', ['id' => $workflow->id]);
        } catch (\Throwable $t) {
            throw new TerminusException(
                'Error creating site workflow: {error_message}',
                ['error_message' => $t->getMessage()]
            );
        }

        // Get site UUID from workflow
        $site_uuid = $workflow->get('waiting_for_task')->params->site_id ?? null;
        if (!$site_uuid) {
            throw new TerminusException('Could not get site ID from site creation workflow.');
        }
        $this->log()->notice('Site creation in process for site ID: {site_id}', ['site_id' => $site_uuid]);

        // Wait for the workflow to complete
        $this->log()->notice('Waiting for site creation workflow to complete...');
        try {
            $this->processWorkflow($workflow);
            $this->log()->notice('Site creation workflow completed successfully.');
        } catch (\Throwable $e) {
            throw new TerminusException(
                'Error during site creation workflow: {error_message}',
                ['error_message' => $e->getMessage()]
            );
        }

        // Get the created site
        $site = $this->getSiteById($site_uuid);
        if (!$site) {
            throw new TerminusException(
                'Failed to retrieve site object (ID: {id}) after workflow completion.',
                ['id' => $site_uuid]
            );
        }

        // Get repository URL from VCS client
        $target_repo_url = null;
        try {
            $site_details = $vcs_client->getSiteDetails($site_uuid);
            $site_details = (array) $site_details['data'][0];
            $target_repo_url = $site_details['repo_url'] ?? null;
        } catch (\Throwable $t) {
            $this->log()->warning('Could not retrieve repository URL: {error}', ['error' => $t->getMessage()]);
        }

        // Clone repository if requested
        $clone_repo = ($options['skip-clone-repo'] == false) && $options['create-repo'];
        if ($clone_repo && $target_repo_url) {
            try {
                $this->cloneRepo($target_repo_url);
            } catch (\Throwable $t) {
                $this->log()->warning(
                    'Error cloning repository: {error_message}',
                    ['error_message' => $t->getMessage()]
                );
            }
        }

        // Wait for the dev environment to be ready (COS sites only).
        // For STA (Node.js) sites, skip waiting and suggest using `node:builds:wait`.
        if ($preferred_platform === 'sta') {
            $this->log()->notice(
                'Site created successfully.'
                    . ' Push a commit to the connected Git repository to trigger your first build.'
                    . ' You can then watch the build status using: terminus node:builds:wait {site}.dev',
                ['site' => $site->getName()]
            );
        } else {
            try {
                $this->waitForDevEnvironment($site, $preferred_platform);
            } catch (TerminusException $e) {
                // If the dev environment fails to wake, log a warning.
                $this->log()->warning(
                    'Error waiting for dev environment to wake: {error_message}',
                    ['error_message' => $e->getMessage()]
                );
                $this->log()->warning(
                    'The site and repository have been created,'
                        . ' but the dev environment may be not yet available.'
                );
            }
        }

        // Final Success Message
        $this->log()->notice('---');
        $this->log()->notice(
            'Site "{site}" created successfully with external repository!',
            ['site' => $site->getName()]
        );
        if ($target_repo_url) {
            $this->log()->notice('Repository: {url}', ['url' => $target_repo_url]);
        }
        $dashboard_url = $site->dashboardUrl($pantheon_org->id);
        $this->log()->notice('Pantheon Dashboard: {url}', ['url' => $dashboard_url]);
        if ($clone_repo) {
            $this->log()->notice('Code repository cloned successfully to the current directory.');
        }
    }

    /**
     * Deletes a site (simple wrapper for error handling).
     */
    protected function deleteSite(string $site_uuid): void
    {
        try {
            $site = $this->sites()->get($site_uuid);
            if ($site) {
                $workflow = $site->delete();
                $workflow->setOwnerObject($this->session()->getUser());
                $this->processWorkflow($workflow);
                $this->log()->notice('Pantheon site cleanup successful.');
            }
        } catch (\Throwable $t) {
            $this->log()->warning('Could not clean up site: {error}', ['error' => $t->getMessage()]);
        }
    }

    /**
     * Clone the repository using the converted SSH URL.
     */
    private function cloneRepo($repo_url)
    {
        $repo_url = $this->convertToSsh($repo_url);

        // Run git clone command
        $process = new Process(['git', 'clone', $repo_url]);
        $process->run();

        if (!$process->isSuccessful()) {
            // @codingStandardsIgnoreLine
            throw new ProcessFailedException($process);
        }

        $this->log()->notice($process->getOutput());
    }

    /**
     * Convert the repository URL to SSH format.
     */
    private function convertToSsh($repo_url)
    {
        $parsedUrl = parse_url($repo_url);
        if (!isset($parsedUrl['host'], $parsedUrl['path'])) {
            // @codingStandardsIgnoreLine
            throw new TerminusException('Invalid repository URL: {repo_url}', ['repo_url' => $repo_url]);
        }

        $host = $parsedUrl['host'];
        $path = ltrim($parsedUrl['path'], '/');

        // Remove .git if present to avoid duplication
        $path = preg_replace('/\.git$/', '', $path);
        $sshUrl = "git@$host:$path.git";

        $this->log()->notice('Converted repository URL: {repo_url}', ['repo_url' => $sshUrl]);
        return $sshUrl;
    }

    /**
     * Get site type as expected by ICR site creation API.
     * Uses the original upstream, not the ICR one.
     */
    protected function getSiteType(Upstream $upstream): string
    {
        $framework = $upstream->get('framework');
        if (empty($framework)) {
            throw new TerminusException('Cannot determine site type for custom upstream without framework.');
        }
        switch ($framework) {
            case 'drupal8':
                return 'cms-drupal';
            case 'wordpress':
            case 'wordpress_network':
                return 'cms-wordpress';
            case 'nodejs':
                return 'nodejs';
            default:
                throw new TerminusException(
                    'Framework {framework} not currently supported for external VCS site creation.',
                    compact('framework')
                );
        }
    }

    /**
     * Get preferred platform based on framework/site_type.
     */
    private function getPreferredPlatformForFramework($site_type): string
    {
        // ATM only nodejs framework is supported on the STA platform update it when more frameworks are supported.
        // Map site_type back to framework logic if needed, or use site_type directly
        if ($site_type == 'nodejs') {
            return 'sta';
        }
        // Default to 'cos' for cms-drupal, cms-wordpress etc.
        return 'cos';
    }

    /**
     * Handle new installation based on VCS provider.
     * Currently only supports GitHub.
     */
    protected function handleNewInstallation(
        string $vcs_provider,
        ?string $auth_url,
        string $flag_file,
        array $options,
        $pantheon_org = null
    ): bool {
        $this->log()->warning(
            "Important: Connecting this application grants all members of this"
                . " Pantheon Workspace the ability to list and create repositories"
                . " in the attached VCS Organization, regardless of their"
                . " individual VCS permissions."
        );
        switch ($vcs_provider) {
            case 'github':
                return $this->handleGithubNewInstallation($auth_url, $flag_file, self::AUTH_LINK_TIMEOUT);

            case 'gitlab':
                return $this->handleGitLabNewInstallation($options, $pantheon_org);
        }
        return false;
    }

    /**
     * Handle GitLab new installation during site creation.
     */
    protected function handleGitLabNewInstallation(array $options, $pantheon_org = null): bool
    {
        $token = $options['vcs-token'] ?? null;
        if (empty($token) && !$this->input()->isInteractive()) {
            throw new TerminusException(
                'GitLab installation requires a token.'
                    . ' Please provide --vcs-token or run interactively.'
            );
        }
        if (empty($token)) {
            $this->log()->notice(
                'A GitLab Group Access Token (Premium/self-hosted) or Personal Access Token is required.'
                    . ' The token must have "api" and "write_repository" scopes.'
            );

            $helper = new QuestionHelper();
            $question = new Question('Enter your GitLab token: ');
            $question->setValidator(function ($answer) {
                if (empty(trim($answer ?? ''))) {
                    throw new \RuntimeException('GitLab token cannot be empty.');
                }
                return trim($answer);
            });
            $question->setMaxAttempts(3);
            $question->setHidden(true);
            $token = $helper->ask($this->input(), $this->output(), $question);
        }

        $helper = $helper ?? new QuestionHelper();
        $question = new Question('Enter the GitLab group name/path: ');
        $question->setValidator(function ($answer) {
            if (empty(trim($answer ?? ''))) {
                throw new TerminusException('Group name cannot be empty');
            }
            return trim($answer);
        });
        $question->setMaxAttempts(3);
        $group_name = $helper->ask($this->input(), $this->output(), $question);

        $user = $this->session()->getUser();

        $post_data = [
            'token' => $token,
            'vendor' => 2,
            'installation_type' => 'cms-site',
            'platform_user' => $user->id,
            'org_uuid' => $pantheon_org ? $pantheon_org->id : '',
            'vcs_organization' => $group_name,
        ];

        $hostname = $options['vcs-host'] ?? null;
        if (!empty($hostname)) {
            $post_data['hostname'] = $hostname;
        }

        $this->log()->notice('Registering GitLab connection...');
        $this->getVcsClient()->installWithToken($post_data);

        return true;
    }
}
