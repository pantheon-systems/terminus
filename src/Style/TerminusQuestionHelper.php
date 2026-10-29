<?php

namespace Pantheon\Terminus\Style;

use Pantheon\Terminus\Helpers\Utility\ProgramStatus;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class TerminusQuestionHelper
 *
 * A QuestionHelper that reports blocked to the terminal while the user
 * answers. Experimental, see ProgramStatus.
 *
 * @experimental
 * @package Pantheon\Terminus\Style
 */
class TerminusQuestionHelper extends QuestionHelper
{
    public function ask(InputInterface $input, OutputInterface $output, Question $question): mixed
    {
        return ProgramStatus::whileAsking(
            $input,
            $output,
            $question->getQuestion(),
            fn () => parent::ask($input, $output, $question)
        );
    }
}
