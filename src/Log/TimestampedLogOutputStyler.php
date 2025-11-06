<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Log;

use Consolidation\Log\LogOutputStyler;

/**
 * Extended log output styler that adds timestamps to all log messages.
 *
 * Formats timestamps as "YYYY-MM-DD hh:mm:ss UTC[+/-offset]" in local timezone.
 */
class TimestampedLogOutputStyler extends LogOutputStyler
{
    /**
     * Generate a timestamp string in the format "YYYY-MM-DD hh:mm:ss UTC[+/-offset]"
     *
     * @return string The formatted timestamp
     */
    private function getTimestamp(): string
    {
        $now = new \DateTime();
        $timezone = $now->getTimezone();
        $offset = $now->getOffset();

        // Format the offset as +/-HHMM
        $offsetHours = abs($offset) / 3600;
        $offsetMinutes = (abs($offset) % 3600) / 60;
        $offsetSign = $offset >= 0 ? '+' : '-';
        $offsetFormatted = sprintf('%s%02d%02d', $offsetSign, $offsetHours, $offsetMinutes);
        return $now->format('Y-m-d H:i:s') . ' UTC[' . $offsetFormatted . ']';
    }

    /**
     * Apply styling with the provided label and message styles, with timestamp prepended.
     *
     * @param string $label The log level label
     * @param string $message The log message
     * @param array $context The log context
     * @param string $labelStyle The style for the label
     * @param string $messageStyle The style for the message
     * @return string The formatted log message with timestamp
     */
    protected function formatMessage($label, $message, $context, $labelStyle, $messageStyle = '')
    {
        // Get the original formatted message with colors
        $originalMessage = parent::formatMessage($label, $message, $context, $labelStyle, $messageStyle);

        // Prepend timestamp to the original formatted message
        return $this->getTimestamp() . $originalMessage;
    }
}
