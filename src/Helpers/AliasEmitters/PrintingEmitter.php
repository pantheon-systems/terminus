<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Helpers\AliasEmitters;

use Symfony\Component\Console\Output\OutputInterface;

class PrintingEmitter extends AliasesDrushRcBase
{
    protected OutputInterface $output;

    /**
     * PrintingEmitter constructor
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function notificationMessage(): string
    {
        return 'Displaying Drush 8 alias file contents.';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function write(array $alias_replacements): void
    {
        $alias_file_contents = $this->getAliasContents($alias_replacements);
        $this->output->writeln($alias_file_contents);
    }
}
