<?php

namespace Pantheon\Terminus\Helpers\Utility;

class TraceId
{
    private static $traceId;

    /**
     * Generate UUID for use as distributed tracing ID and assign to static class variable
     */
    public static function generateTraceId()
    {
        self::$traceId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
    }

    /**
     * Get the generated trace ID
     *
     * @return string
     */
    public static function getTraceId()
    {
        return self::$traceId;
    }
}
