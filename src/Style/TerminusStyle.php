<?php

namespace Pantheon\Terminus\Style;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class TerminusStyle
 * @package Pantheon\Terminus\Style
 */
class TerminusStyle extends SymfonyStyle
{
    const NORMAL_PROGRESS_FORMAT = " <bg=blue;fg=black> %message% </>\n <info>Progress: %bar% <info>%percent:3s%%</info> \n <info>Elapsed: %elapsed:6s% \n Estimated Time Remaining: %estimated:-6s%</info>";

    /**
     * TerminusStyle constructor.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        ProgressBar::setFormatDefinition('normal', self::NORMAL_PROGRESS_FORMAT);
        parent::__construct($input, $output);
    }
}
