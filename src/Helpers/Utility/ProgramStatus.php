<?php

namespace Pantheon\Terminus\Helpers\Utility;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class ProgramStatus
 *
 * Reports what Terminus is doing to the terminal with the Program Status
 * Protocol (OSC 7501), so terminals that support it can show that a workflow
 * is running, finished, or failed. Terminals that don't support it ignore the
 * sequence.
 *
 * Experimental: off by default, turned on with TERMINUS_PROGRAM_STATUS=1.
 *
 * @experimental
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
     * The running command's name, shown while it works.
     *
     * @var string|null
     */
    protected static $command = null;

    /**
     * Whether a done or error report has been sent since the last working
     * or blocked one.
     *
     * @var bool
     */
    protected static $finished = false;

    /**
     * @var bool
     */
    protected static $enabled = false;

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
        $this->output = ($enabled && $output !== null && $output->isDecorated() && self::isTerminal($output))
            ? $output
            : null;
    }

    /**
     * Checks that a stream output is a terminal. Forced decoration (--ansi)
     * makes redirected output decorated, but it is still not a terminal.
     *
     * @param OutputInterface $output
     * @return bool
     */
    protected static function isTerminal(OutputInterface $output): bool
    {
        if (!$output instanceof StreamOutput) {
            return true;
        }
        $stream = $output->getStream();
        if (function_exists('stream_isatty')) {
            return @stream_isatty($stream);
        }
        return function_exists('posix_isatty') && @posix_isatty($stream);
    }

    /**
     * Reads the program_status setting (TERMINUS_PROGRAM_STATUS). Reporting
     * is experimental and off unless the setting turns it on.
     *
     * @param \Consolidation\Config\ConfigInterface|null $config
     * @return bool
     */
    public static function isEnabled($config): bool
    {
        if ($config === null) {
            return false;
        }
        $value = $config->get('program_status', false);
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
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
     * Reports that the command is waiting for the user to answer a prompt.
     *
     * @param string $message
     */
    public function blocked(string $message)
    {
        $this->report('blocked', ['kind' => 'question', 'msg' => $message]);
    }

    /**
     * Reports that the command named in the input has started, and
     * remembers it for the reports that follow.
     *
     * @param InputInterface $input
     */
    public function started(InputInterface $input)
    {
        $command = $input->getFirstArgument();
        self::$command = empty($command) ? null : (string) $command;
        self::$finished = false;
        $this->resume();
    }

    /**
     * Reports that the running command is working again, after a prompt.
     */
    public function resume()
    {
        if (self::$command !== null) {
            $this->working(self::$command);
        }
    }

    /**
     * Reports how the command ended, unless a workflow already reported done
     * or error. A failed command reports error. A successful one clears its
     * working report, since there is nothing new for the user to look at.
     *
     * @param int $status_code
     */
    public function finished(int $status_code)
    {
        if (self::$finished || self::$command === null) {
            return;
        }
        if ($status_code !== 0) {
            $this->error(self::$command . ' failed.');
            return;
        }
        $this->report('clear');
    }

    /**
     * Reports blocked while the user answers a prompt, then working again.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $prompt
     * @param callable $ask Asks the question and returns the answer.
     * @return mixed The answer.
     */
    public static function whileAsking(InputInterface $input, OutputInterface $output, string $prompt, callable $ask)
    {
        if (!$input->isInteractive()) {
            return $ask();
        }
        $status = new self($output, self::$enabled);
        $status->blocked(strip_tags($prompt));
        try {
            return $ask();
        } finally {
            $status->resume();
        }
    }

    /**
     * Turns reporting from prompts on or off, from the program_status setting.
     * Prompts are asked by helpers that have no access to config.
     *
     * @param bool $enabled
     */
    public static function setEnabled(bool $enabled)
    {
        self::$enabled = $enabled;
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
        if (in_array($state, ['done', 'error'], true)) {
            self::$finished = true;
        } elseif (in_array($state, ['working', 'blocked'], true)) {
            self::$finished = false;
        }
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
