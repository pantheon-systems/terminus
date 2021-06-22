<?php

namespace Pantheon\Terminus\Commands\Doc;

use Behat\Testwork\Output\Printer\Factory\FilesystemOutputFactory;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Helpers\Utility\ReadmeDescriptor;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Robo\Common\IO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class DocCommand
 * @package Pantheon\Terminus\Commands\Help
 */
class DocCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use StructuredListTrait;
    use IO;

    /**
     * Generate Documentation from command files and output to OutputInterface.
     *
     *
     * @command doc:generate
     * @option file Output README content to a f
     *
     * @return RowsOfFields
     *
     *
     * @usage doc:generate Gather's documentation and creates README content.
     */
    public function generate($options = ['file' => null])
    {
        $context = $this->getConfig()->exportAll()['process'] ?? [];
        $loader = new FilesystemLoader($context['root'] . DIRECTORY_SEPARATOR . "templates");
        $twig = new Environment($loader, [
            'cache' => false
        ]);
        $twig->getExtension(\Twig\Extension\EscaperExtension::class)
            ->setEscaper('raw', 'utf8_decode');
        $description = new ApplicationDescription(
            $this->getContainer()->get('application')
        );

        $context['commands'] = $commands = array_map(function (Command $command) {
            [$group] = explode(":", $command->getName());
            return [
                "group" => $group,
                "command" => $command->getName(),
                "description" => explode(
                    PHP_EOL,
                    wordwrap(
                        html_entity_decode($command->getDescription()),
                        50,
                        PHP_EOL
                    )
                )
            ];
        }, $description->getCommands());
        $output = $twig->render('README.twig', $context);
        if ($options['file'] !== null) {
            file_put_contents($context['root'] . DIRECTORY_SEPARATOR . $options['file'], $output);
            return;
        }
        $this->output()->write($output);
    }
}
