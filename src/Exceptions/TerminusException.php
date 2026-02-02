<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Exceptions;

/**
 * Class TerminusException
 * @package Pantheon\Terminus\Exceptions
 */
class TerminusException extends \Exception
{
    /**
     * @var array
     */
    private array $replacements;

    /**
     * @var null|string
     */
    private ?string $raw_message;

    /**
     * Object constructor. Sets context array as replacements property.
     *
     * @param string|array|null $message      Message to send when throwing the exception.
     * @param array  $replacements Context array to interpolate into message.
     * @param int    $code         The Exception code.
     */
    public function __construct(
        string|array|null $message = null,
        array $replacements = [],
        int $code = 0
    ) {
        $this->replacements = $replacements;
        $this->raw_message = is_array($message) ? implode(PHP_EOL, $message) : $message;

        parent::__construct($this->interpolateString($message, $replacements), $code);
    }

    /**
     * Returns the replacements context array
     *
     * @return string $this->replacements
     */
    public function getRawMessage(): ?string
    {
        return $this->raw_message;
    }

    /**
     * Returns the replacements context array
     *
     * @return array $this->replacements The replacement variables.
     */
    public function getReplacements(): array
    {
        return $this->replacements;
    }

    /**
     * Replace the variables into the message string.
     *
     * @param string $message      The raw, uninterpolated message string
     * @param array  $replacements The values to replace into the message
     * @return string
     */
    protected function interpolateString(string|array|null $message, array $replacements): string
    {
        $tr = [];
        foreach ($replacements as $key => $val) {
            $tr['{' . $key . '}'] = $val;
        }
        if (is_array($message)) {
            $message = implode(PHP_EOL, $message);
        }
        return strtr($message ?? '', $tr);
    }
}
