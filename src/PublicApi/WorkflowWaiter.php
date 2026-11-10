<?php

namespace Pantheon\Terminus\PublicApi;

use Closure;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Waits for a Public API v1 workflow to finish.
 *
 * Mutations return a WorkflowPayload with the status nested:
 *   {"id": "<wid>", "status": {"id": "<wid>", "phase": "STARTED", "result": null, ...}}
 * while GET /sites/{siteId}/workflows/{wid} returns the WorkflowStatus flat:
 *   {"id": "<wid>", "phase": "FINISHED", "result": "SUCCEEDED", "progress": 100, "reason": [], ...}
 *
 * phase: CREATED | QUEUED | STARTED | FINISHED
 * result: ACTIVE | FAILED | SUCCEEDED | STATIC | ABORTED | UNAUTHORIZED
 *
 * This is the v1 counterpart of WorkflowProcessingTrait::processWorkflow(), deliberately separate
 * from the legacy Workflow model.
 */
class WorkflowWaiter
{
    /**
     * The API will not allow polling faster than once per second (same rule as processWorkflow()).
     */
    public const MIN_POLL_DELAY_MS = 1000;

    public const DEFAULT_POLL_DELAY_MS = 5000;

    private Client $client;

    private int $pollDelayMs;

    private Closure $sleep;

    private Closure $clock;

    private ?Closure $onProgress;

    /**
     * @param Client $client
     * @param int $pollDelayMs Config `workflow_polling_delay_ms`; raised to at least 1000.
     * @param Closure|null $onProgress fn(array $status): void, called after every poll.
     * @param Closure|null $sleep fn(int $milliseconds): void. Tests pass a no-op.
     * @param Closure|null $clock fn(): int, current time in seconds. Tests pass a fake.
     */
    public function __construct(
        Client $client,
        int $pollDelayMs = self::DEFAULT_POLL_DELAY_MS,
        ?Closure $onProgress = null,
        ?Closure $sleep = null,
        ?Closure $clock = null
    ) {
        $this->client = $client;
        $this->pollDelayMs = max(self::MIN_POLL_DELAY_MS, $pollDelayMs);
        $this->onProgress = $onProgress;
        $this->sleep = $sleep ?? fn (int $ms) => usleep($ms * 1000);
        $this->clock = $clock ?? fn () => time();
    }

    /**
     * Polls until the workflow finishes, and returns its final (flat) WorkflowStatus.
     *
     * @param string $siteId
     * @param array $payload The WorkflowPayload returned by the mutation.
     * @param int $timeoutSeconds 0 waits indefinitely.
     *
     * @return array
     * @throws TerminusException On failure or timeout.
     * @throws PublicApiException
     */
    public function wait(string $siteId, array $payload, int $timeoutSeconds = 0): array
    {
        $workflowId = self::workflowId($payload);
        $status = isset($payload['status']) && is_array($payload['status']) ? $payload['status'] : [];
        $deadline = $timeoutSeconds > 0 ? ($this->clock)() + $timeoutSeconds : 0;
        $path = Client::path('/sites/%s/workflows/%s', $siteId, $workflowId);

        while (($status['phase'] ?? null) !== 'FINISHED') {
            if ($deadline > 0 && ($this->clock)() >= $deadline) {
                throw new TerminusException(
                    'Workflow {id} timed out after {timeout} seconds.',
                    ['id' => $workflowId, 'timeout' => $timeoutSeconds]
                );
            }
            ($this->sleep)($this->pollDelayMs);
            $status = (array) $this->client->get($path);
            if ($this->onProgress !== null) {
                ($this->onProgress)($status);
            }
        }

        if (($status['result'] ?? null) !== 'SUCCEEDED') {
            throw new TerminusException('{message}', ['message' => self::failureMessage($workflowId, $status)]);
        }
        return $status;
    }

    /**
     * @throws TerminusException
     */
    private static function workflowId(array $payload): string
    {
        $id = $payload['id'] ?? ($payload['status']['id'] ?? null);
        if (!is_string($id) || $id === '') {
            throw new TerminusException('The API response did not include a workflow ID.');
        }
        return $id;
    }

    private static function failureMessage(string $workflowId, array $status): string
    {
        $description = $status['description'] ?? $status['activeDescription'] ?? 'Workflow';
        $reasons = array_filter((array) ($status['reason'] ?? []), 'is_string');
        $message = sprintf(
            '%s failed (result: %s, workflow %s)',
            $description,
            $status['result'] ?? 'unknown',
            $workflowId
        );
        return $reasons ? $message . ': ' . implode('; ', $reasons) : $message . '.';
    }
}
