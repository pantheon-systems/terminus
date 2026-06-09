<?php

namespace Pantheon\Terminus\Commands\Vcs\Connection;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\VcsApi\VcsClientAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Symfony\Component\Process\Process;
use Pantheon\Terminus\Traits\GithubInstallTrait;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;

/**
 * Class AddCommand.
 *
 * @package Pantheon\Terminus\Commands\Vcs\Connection
 */
class AddCommand extends TerminusCommand implements RequestAwareInterface
{
    use VcsClientAwareTrait;
    use GithubInstallTrait;

    protected Process $serverProcess;

    protected const AUTH_LINK_TIMEOUT = 600;
    protected const REDIRECT_URL = 'https://docs.pantheon.io/github-application';

    public function __destruct()
    {
        if (isset($this->serverProcess) && $this->serverProcess->isRunning()) {
            $this->serverProcess->stop(0);
        }
    }

    /**
     * Registers a VCS installation with the VCS API.
     *
     * @authorize
     *
     * @command vcs:connection:add
     * @aliases vcs-connection-add
     *
     * @param string $organization Organization name, label, or ID.
     * @option vcs-provider VCS provider (github or gitlab). Default is github.
     * @option vcs-host Hostname of a self-hosted instance (e.g., ghes.example.com or gitlab.example.com).
     * @option vcs-token Access token for the VCS provider. Only applies to GitLab.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <organization> Registers a VCS installation with the VCS API.
     * @usage <organization> --vcs-provider=gitlab Registers a GitLab installation.
     * @usage <organization> --vcs-provider=gitlab --vcs-host=gitlab.example.com Registers a self-hosted GitLab installation.
     */
    public function connectionAdd(string $organization, array $options = [
        'vcs-provider' => 'github',
        'vcs-host' => null,
        'vcs-token' => null,
    ])
    {
        $vcsProvider = $options['vcs-provider'] ?? 'github';

        $organization = $this->session()->getUser()->getOrganizationMemberships()->get(
            $organization
        )->getOrganization();

        $this->log()->warning(
            "Keep in mind that any member of the selected Pantheon Workspace"
                . " will be able to list and create repositories in the selected VCS organization."
        );

        switch ($vcsProvider) {
            case 'github':
                $this->connectGithub($organization, $options);
                break;
            case 'gitlab':
                $this->connectGitlab($organization, $options);
                break;
            default:
                throw new TerminusException(
                    'Unsupported VCS provider: {provider}. Supported providers are: github, gitlab.',
                    ['provider' => $vcsProvider]
                );
        }
    }

    public function connectGithub($organization, array $options = [])
    {
        list($url, $flag_file, $process) = $this->startTemporaryServer();
        // Store the process so we can stop it later.
        $this->serverProcess = $process;

        $github_host = $options['vcs-host'] ?? null;
        $auth_links_resp = $this->getVcsClient()->getAuthLinks(
            $organization->id,
            $this->session()->getUser()->id,
            "cms-drupal",
            $url,
            $github_host
        );
        $auth_links = $auth_links_resp['data'] ?? null;
        $this->log()->debug('VCS Auth Links: {auth_links}', ['auth_links' => print_r($auth_links, true)]);
        $auth_url = null;
        // Iterate over the two possible auth options for the given VCS.
        foreach (['app', 'oauth'] as $auth_option) {
            if (isset($auth_links->{sprintf("github_%s", $auth_option)})) {
                $auth_url = sprintf('"%s"', $auth_links->{sprintf("github_%s", $auth_option)});
                break;
            }
        }
        if (is_null($auth_url)) {
            throw new TerminusException('No authentication URL found for the GitHub VCS provider.');
        }

        $success = $this->handleGithubNewInstallation($auth_url, $flag_file, self::AUTH_LINK_TIMEOUT);
        if (!$success) {
            throw new TerminusException('GitHub App installation was not completed within the timeout period.');
        }
        $this->log()->notice('GitHub App installation completed successfully.');
    }

    public function connectGitlab($organization, array $options = [])
    {
        $token = $options['vcs-token'] ?? null;
        $hostname = $options['vcs-host'] ?? null;

        if (empty($token) && !$this->input()->isInteractive()) {
            throw new TerminusException(
                'GitLab installation requires a token.'
                    . ' Please provide --vcs-token or run interactively.'
            );
        }

        if (empty($token)) {
            $this->log()->notice(
                'A GitLab Group Access Token (Premium/self-hosted) or Personal Access Token is required.'
                    . ' The token must have the "api" scope.'
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
                throw new \RuntimeException('Group name cannot be empty.');
            }
            return trim($answer);
        });
        $question->setMaxAttempts(3);
        $groupName = $helper->ask($this->input(), $this->output(), $question);

        $user = $this->session()->getUser();

        $post_data = [
            'token' => $token,
            'vendor' => 2,
            'installation_type' => 'cms-site',
            'platform_user' => $user->id,
            'org_uuid' => $organization->id,
            'vcs_organization' => $groupName,
        ];

        if (!empty($hostname)) {
            $post_data['hostname'] = $hostname;
        }

        $this->log()->notice('Registering GitLab connection...');
        $data = $this->getVcsClient()->installWithToken($post_data);

        $this->log()->notice('GitLab installation completed successfully.');
    }
}
