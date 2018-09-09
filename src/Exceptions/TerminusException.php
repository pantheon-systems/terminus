<?php

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
    private $replacements;

    /**
     * @var null|string
     */
    private $raw_message;

  /**
   * Object constructor. Sets context array as replacements property
   *
   * @param string $message      Message to send when throwing the exception
   * @param array  $replacements Context array to interpolate into message
   * @param int    $code         Exit code
   */
    public function __construct(
        $message = null,
        array $replacements = [],
        $code = 0
    ) {
        $this->replacements = $replacements;
        $this->raw_message = $message;

        parent::__construct($this->interpolateString($message, $replacements), $code);
    }

    /**
     * Returns the replacements context array
     *
     * @return string $this->replacements
     */
    public function getRawMessage()
    {
        return $this->raw_message;
    }

    /**
     * Returns the replacements context array
     *
     * @return array $this->replacements The replacement variables.
     */
    public function getReplacements()
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
    protected function interpolateString($message, $replacements)
    {
        $tr = [];
        foreach ($replacements as $key => $val) {
            $tr['{' . $key . '}'] = $val;
        }
        if (is_array($message)) {
            $message = implode(PHP_EOL, $message);
        }
        return strtr($message, $tr);
    }
}
