<?php

namespace Pantheon\Terminus\Helpers\Utility;

class Timing
{
    private static $startTime;

    /**
     * Generate start time and assign to static class variable
     */
    public static function generateStartTime()
    {
        self::$startTime = new \DateTime();
    }

    /**
     * Get the start time
     *
     * @return DateTime
     */
    public static function getStartTime()
    {
        if (empty(self::$startTime)) { // If startTime is not set, generate it
            self::generateStartTime();
        }
        return self::$startTime;
    }
}
