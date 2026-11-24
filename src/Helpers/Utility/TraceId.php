<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Helpers\Utility;

class TraceId
{
    private static ?string $traceId = null;

    /**
     * Generate UUID for use as distributed tracing ID and assign to static class variable
     */
    public static function generateTraceId(): void
    {
        self::$traceId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
    }

    /**
     * Get the generated trace ID
     *
     * @return string
     */
    public static function getTraceId(): string
    {
        if (self::$traceId === null) { // If trace ID is not set, generate it
            self::generateTraceId();
        }
        return self::$traceId;
    }
}
