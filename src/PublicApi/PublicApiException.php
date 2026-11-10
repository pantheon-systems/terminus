<?php

namespace Pantheon\Terminus\PublicApi;

use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * An error response (or transport failure) from the Pantheon Public API v1.
 *
 * Extends TerminusException so Terminus reports it like any other command error.
 */
class PublicApiException extends TerminusException
{
    private ?int $statusCode;

    private ?string $traceId;

    private ?string $errorCode;

    /**
     * @param string $message Human-readable message (not interpolated).
     * @param int|null $statusCode HTTP status, or null for transport failures.
     * @param string|null $errorCode The v1 `errors[0].extensions.code`, e.g. UNAUTHORIZED.
     * @param string|null $traceId The v1 `traceId`, useful when reporting a problem.
     */
    public function __construct(
        string $message,
        ?int $statusCode = null,
        ?string $errorCode = null,
        ?string $traceId = null
    ) {
        $this->statusCode = $statusCode;
        $this->errorCode = $errorCode;
        $this->traceId = $traceId;
        // Pass the message as a replacement so braces in API text are not interpolated.
        parent::__construct('{message}', ['message' => $message], $statusCode ?? 0);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getTraceId(): ?string
    {
        return $this->traceId;
    }
}
