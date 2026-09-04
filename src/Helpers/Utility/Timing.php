<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Helpers\Utility;

class Timing
{
    private static ?\DateTime $startTime = null;

    /**
     * Generate start time and assign to static class variable
     */
    public static function generateStartTime(): void
    {
        self::$startTime = new \DateTime();
    }

    /**
     * Get the start time
     *
     * @return \DateTime
     */
    public static function getStartTime(): \DateTime
    {
        if (self::$startTime === null) { // If startTime is not set, generate it
            self::generateStartTime();
        }
        return self::$startTime;
    }
}
