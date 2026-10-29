<?php

namespace Pantheon\Terminus\Helpers\Utility;

use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProgramStatus
 *
 * Reports what Terminus is doing to the terminal with the Program Status
 * Protocol (OSC 7501), so terminals that support it can show that a workflow
 * is running, finished, or failed. Terminals that don't support it ignore the
 * sequence.
 *
 * @see https://www.superlogical.com/rex/docs/build/program-status
 *
 * @package Pantheon\Terminus\Helpers\Utility
 */
class ProgramStatus
{
    public const APP = 'terminus';

    // The protocol caps the decoded msg at 2048 bytes.
    public const MAX_MESSAGE_BYTES = 2048;

    /**
     * @var OutputInterface|null
     */
    protected $output;

    /**
     * ProgramStatus constructor.
     *
     * @param OutputInterface|null $output Where to write reports. Reports
     *   are only written when this output is decorated (a terminal).
     * @param bool $enabled Whether to report at all.
     */
    public function __construct(?OutputInterface $output, bool $enabled = true)
    {
        if ($output instanceof ConsoleOutputInterface) {
            // Write alongside the progress bar, which also uses stderr.
            $output = $output->getErrorOutput();
        }
        $this->output = ($enabled && $output !== null && $output->isDecorated()) ? $output : null;
    }

    /**
     * Reads the program_status setting (TERMINUS_PROGRAM_STATUS), which
     * defaults to on.
     *
     * @param \Consolidation\Config\ConfigInterface|null $config
     * @return bool
     */
    public static function isEnabled($config): bool
    {
        if ($config === null) {
            return true;
        }
        $value = $config->get('program_status', true);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
    }

    /**
     * Reports that work is in progress.
     *
     * @param string $message
     * @param int|null $progress Percentage from 0 to 100, or null if unknown.
     */
    public function working(string $message, ?int $progress = null)
    {
        $pairs = ['msg' => $message];
        if ($progress !== null) {
            $pairs['progress'] = max(0, min(100, $progress));
        }
        $this->report('working', $pairs);
    }

    /**
     * Reports that the work finished successfully.
     *
     * @param string $message
     */
    public function done(string $message)
    {
        $this->report('done', ['msg' => $message]);
    }

    /**
     * Reports that the work failed.
     *
     * @param string $message
     */
    public function error(string $message)
    {
        $this->report('error', ['msg' => $message]);
    }

    /**
     * Writes a report to the terminal, if reporting is enabled.
     *
     * @param string $state
     * @param array $pairs
     */
    protected function report(string $state, array $pairs = [])
    {
        if ($this->output === null) {
            return;
        }
        $this->output->write(self::format($state, $pairs), false, OutputInterface::OUTPUT_RAW);
    }

    /**
     * Builds an OSC 7501 report.
     *
     * @param string $state
     * @param array $pairs Extra keys. msg and title are base64 encoded here.
     * @return string
     */
    public static function format(string $state, array $pairs = []): string
    {
        $body = ['state=' . $state, 'app=' . self::APP];
        foreach ($pairs as $key => $value) {
            if (in_array($key, ['msg', 'title'], true)) {
                $value = self::sanitize((string) $value);
                if ($value === '') {
                    continue;
                }
                $value = base64_encode($value);
            }
            $body[] = $key . '=' . $value;
        }
        return "\e]7501;" . implode(':', $body) . "\e\\";
    }

    /**
     * Makes text safe for msg: one line, no control characters, within the
     * size limit. A report with a control character is discarded whole.
     *
     * @param string $text
     * @return string
     */
    public static function sanitize(string $text): string
    {
        $text = mb_scrub($text, 'UTF-8');
        $text = preg_replace('/[\x{0000}-\x{001F}\x{007F}-\x{009F}]+/u', ' ', $text);
        $text = trim(preg_replace('/ {2,}/', ' ', $text));
        return mb_strcut($text, 0, self::MAX_MESSAGE_BYTES, 'UTF-8');
    }
}
