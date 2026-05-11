<?php

namespace Pantheon\Terminus\Commands\Node;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;

/**
 * Class BuildsWaitCommand.
 *
 * @package Pantheon\Terminus\Commands\Node
 */
class BuildsWaitCommand extends TerminusCommand implements SiteAwareInterface, RequestAwareInterface
{
    use SiteAwareTrait;
    use RequestAwareTrait;

    private const IN_PROGRESS_STATUSES = [
        'BUILD_PENDING',
        'BUILD_QUEUED',
        'BUILD_WORKING',
        'BUILD_SUCCESS',
        'DEPLOYMENT_WORKING',
        'DEPLOYMENT_QUEUED',
    ];

    private const FAILURE_STATUSES = [
        'BUILD_FAILURE',
        'BUILD_TIMEOUT',
        'BUILD_CANCELLED',
        'DEPLOYMENT_FAILURE',
        'DEPLOYMENT_CANCELLED',
    ];

    private const SUCCESS_STATUS = 'DEPLOYMENT_SUCCESS';

    /**
     * Wait for a Node.js site build and deployment to complete.
     *
     * @authorize
     *
     * @command node:builds:wait
     * @aliases nbw,node:build:wait
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option string $commit Commit SHA to wait for (optional; waits for latest build if omitted)
     * @option int $max Maximum number of seconds to wait for the build to complete
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <site>.<env> Wait for the latest build on <site>'s <env> environment.
     * @usage <site>.<env> --commit=<sha> Wait for a specific commit's build on <site>'s <env> environment.
     */
    public function buildsWait(
        $site_env,
        $options = [
            'commit' => '',
            'max' => 600,
        ]
    ) {
        $this->requireSiteIsNotFrozen($site_env);
        $site = $this->getSiteById($site_env);
        $env = $this->getEnv($site_env);

        if (!$site->isEvcs()) {
            throw new TerminusException(
                'This command only works for sites using external VCS (GitHub, GitLab, Bitbucket).'
            );
        }

        if (!$site->isNodejs()) {
            throw new TerminusException(
                'This command only works for Node.js sites.'
            );
        }

        $env_name = $env->getName();
        $target_commit = $options['commit'] ?? '';
        $maxWaitInSeconds = $options['max'];

        if (!empty($target_commit)) {
            if (!preg_match('/^[0-9a-f]{7,40}$/', $target_commit)) {
                throw new TerminusException(
                    'Commit {commit} is not a valid commit SHA (must be 7-40 hexadecimal characters).',
                    ['commit' => $target_commit]
                );
            }
            $this->log()->notice('Waiting for build with commit {commit} on {site} environment {env}.', [
                'commit' => $target_commit,
                'site' => $site->getName(),
                'env' => $env_name,
            ]);
        } else {
            $this->log()->notice('Waiting for latest build on {site} environment {env}.', [
                'site' => $site->getName(),
                'env' => $env_name,
            ]);
        }

        $current_time = time();
        if ($maxWaitInSeconds > 0) {
            $end_time = $current_time + $maxWaitInSeconds;
        } else {
            $end_time = 0;
        }

        $retry_interval = $this->getConfig()->get('workflow_polling_delay_ms', 5000);
        if ($retry_interval < 1000) {
            $retry_interval = 1000;
        }

        $build = null;
        $max_not_found_attempts = 10;
        $not_found_attempts = 0;

        // Phase 1: Find the build
        do {
            $current_time = time();

            if ($end_time > 0 && $current_time >= $end_time) {
                $this->log()->warning(
                    'Waited \'{timeout}\' seconds, giving up waiting for build to be found',
                    ['timeout' => $maxWaitInSeconds]
                );
                return;
            }

            if ($not_found_attempts >= $max_not_found_attempts) {
                $this->log()->warning(
                    'Build not found after {retries} attempts, giving up.',
                    ['retries' => $max_not_found_attempts]
                );
                return;
            }

            $builds = $this->fetchBuilds($site->id, $env_name);

            if (!empty($builds)) {
                $build = $this->findMatchingBuild($builds, $target_commit);
            }

            if ($build) {
                $this->log()->notice('Found build {id} with status {status}.', [
                    'id' => $build->id,
                    'status' => $build->status,
                ]);
                break;
            }

            $not_found_attempts++;
            $this->log()->debug('Build not found, retrying... ({retry}/{max})', [
                'retry' => $not_found_attempts,
                'max' => $max_not_found_attempts,
            ]);
            usleep($retry_interval * 1000);
        } while (true);

        // Phase 2: Wait for the build to reach a terminal state
        // Reset timeout for the wait phase (forgiving, like workflow:wait)
        $current_time = time();
        if ($maxWaitInSeconds > 0) {
            $end_time = $current_time + $maxWaitInSeconds;
        } else {
            $end_time = 0;
        }

        do {
            $current_time = time();

            if ($end_time > 0 && $current_time >= $end_time) {
                $this->log()->warning(
                    'Waited \'{timeout}\' seconds, giving up waiting for build to finish',
                    ['timeout' => $maxWaitInSeconds]
                );
                return;
            }

            // Check current status
            if ($build->status === self::SUCCESS_STATUS) {
                $this->log()->notice('Build {id} deployed successfully.', [
                    'id' => $build->id,
                ]);
                return;
            }

            if (in_array($build->status, self::FAILURE_STATUSES)) {
                throw new TerminusException(
                    'Build {id} failed with status: {status}',
                    ['id' => $build->id, 'status' => $build->status]
                );
            }

            if (!in_array($build->status, self::IN_PROGRESS_STATUSES)) {
                throw new TerminusException(
                    'Build {id} has unexpected status: {status}',
                    ['id' => $build->id, 'status' => $build->status]
                );
            }

            $this->log()->debug('Build {id} status: {status}, waiting...', [
                'id' => $build->id,
                'status' => $build->status,
            ]);

            usleep($retry_interval * 1000);

            // Re-fetch builds to get updated status
            $builds = $this->fetchBuilds($site->id, $env_name);
            $updated_build = $this->findBuildById($builds, $build->id);

            if (!$updated_build) {
                throw new TerminusException(
                    'Build {id} disappeared during execution.',
                    ['id' => $build->id]
                );
            }

            $build = $updated_build;
        } while (true);
    }

    /**
     * Fetch builds from the API.
     *
     * @param string $site_id Site ID.
     * @param string $env Environment name.
     *
     * @return array
     */
    private function fetchBuilds(string $site_id, string $env): array
    {
        $data = $this->getFromUrl(
            sprintf('/api/sites/%s/environment/%s/build/list?%s', $site_id, $env, http_build_query([
                'limit' => 10,
            ]))
        );

        if (empty($data) || !is_array($data)) {
            return [];
        }

        return $data;
    }

    /**
     * Find a build matching the target commit or the latest in-progress/recent build.
     *
     * @param array $builds Array of build objects from the API.
     * @param string $target_commit Commit SHA to match (empty for latest).
     *
     * @return object|null
     */
    private function findMatchingBuild(array $builds, string $target_commit): ?object
    {
        if (!empty($target_commit)) {
            foreach ($builds as $build) {
                if (
                    isset($build->commit) &&
                    strpos($build->commit, $target_commit) === 0
                ) {
                    return $build;
                }
            }
            return null;
        }

        // No commit specified: find the latest in-progress build,
        // or fallback to the most recent build.
        foreach ($builds as $build) {
            if (in_array($build->status, self::IN_PROGRESS_STATUSES)) {
                return $build;
            }
        }

        // If no in-progress build, return the most recent one
        return $builds[0] ?? null;
    }

    /**
     * Find a build by ID in the builds array.
     *
     * @param array $builds Array of build objects.
     * @param string $build_id Build ID to find.
     *
     * @return object|null
     */
    private function findBuildById(array $builds, string $build_id): ?object
    {
        foreach ($builds as $build) {
            if ($build->id === $build_id) {
                return $build;
            }
        }
        return null;
    }

    /**
     * Get data from a given url.
     *
     * @param string $url Url to get data from.
     *
     * @return array|string|null
     */
    private function getFromUrl(string $url)
    {
        $protocol = $this->getConfig()->get('protocol');
        $host = $this->getConfig()->get('host');

        $url = sprintf('%s://%s%s', $protocol, $host, $url);

        $options = [
            'headers' => [
                'X-Pantheon-Session' => $this->request->session()->get('session'),
            ],
        ];
        $result = $this->request()->request($url, $options);
        $status_code = $result->getStatusCode();

        if ($status_code != 200) {
            return null;
        }

        $data = $result->getData();
        if (empty($data)) {
            return null;
        }

        return $data;
    }
}
