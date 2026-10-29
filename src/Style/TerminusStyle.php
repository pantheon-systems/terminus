<?php

namespace Pantheon\Terminus\Style;

use Pantheon\Terminus\Helpers\Utility\ProgramStatus;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class TerminusStyle
 * @package Pantheon\Terminus\Style
 */
class TerminusStyle extends SymfonyStyle
{
    /**
     * @var InputInterface
     */
    private $terminusInput;

    /**
     * @var OutputInterface
     */
    private $terminusOutput;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->terminusInput = $input;
        $this->terminusOutput = $output;
        parent::__construct($input, $output);
    }

    /**
     * Reports blocked to the terminal while the user answers. ask(),
     * askHidden(), confirm() and choice() all come through here.
     *
     * @param Question $question
     * @return mixed
     */
    public function askQuestion(Question $question): mixed
    {
        return ProgramStatus::whileAsking(
            $this->terminusInput,
            $this->terminusOutput,
            $question->getQuestion(),
            fn () => parent::askQuestion($question)
        );
    }
}
