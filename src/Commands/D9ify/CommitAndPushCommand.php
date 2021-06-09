<?php


namespace Pantheon\Terminus\Commands\D9ify;

use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Helpers\Site\Directory;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * This Command allows you to commit all files in the given site and push them up to development.
 *
 *
 * @package Pantheon\Terminus\Commands\D9ify
 */
class CommitAndPushCommand extends TerminusCommand
{

    use SiteAwareTrait;
    use ConfigAwareTrait;
    /**
     * @var string
     */
    public static $HELP_TEXT = [
        "*******************************************************************************",
        "* THIS PROJECT IS IN ALPHA VERSION STATUS AND AT THIS POINT HAS VERY LITTLE   *",
        "* ERROR CHECKING. PLEASE USE AT YOUR OWN RISK.                                *",
        "* The guide to use this file is in /README.md                                 *",
        "*******************************************************************************",
    ];

    /**
     * @var string
     */
    protected static $defaultName = 'd9ify';
    /**
     * @var \Pantheon\Terminus\Helpers\Site\Directory
     */
    protected Directory $sourceDirectory;
    /**
     * @var \Pantheon\Terminus\Helpers\Site\Directory
     */
    protected Directory $destinationDirectory;



    /**
     * Commit uncommitted site files and push to remote repository.
     *
     * @authorize
     * @command d9ify:commitAndPush
     * @aliases d9cp
     *
     * @param string $site
     *   Pantheon Site ID/Name.
     *
     * @return void
     * @usage terminus d9ify:commitAndPush {SiteName}
     *
     */
    protected function commitAndPush(string $site)
    {
        $this->setDestinationDirectory(
            Directory::factory(
                $this->input()->getArgument('destination') ??
                $this->sourceDirectory->getSiteInfo()->getName() . "-" . date('Y'),
                $this->output(),
            )
        );
        $this->restoreDatabaseToDestinationSite($this->output());
        $this->unpackSiteFilesAndRsyncToDestination($this->output());
        $this->checkinVersionManagedFilesAndPush($this->output());
    }




    /**
     * @step TODO: unpack site files archive and rsync them up.
     * @description
     * There's a hard limit to the size archive you can upload. We'll do an rysnc
     * but if/when it times out, we need a way of restarting the rsync.
     *
     */
    public function unpackSiteFilesAndRsyncToDestination(OutputInterface $output)
    {
        $output->writeln("===> TODO: unpack files archive and rsync to destination");
    }

    /**
     * @step TODO: check in the version-managed files
     * @description
     * Push them up to dev environment.
     *
     */
    public function checkinVersionManagedFilesAndPush(OutputInterface $output)
    {
        $output->writeln("===> Restore database to destination: ");
        $getMysqlCommand = sprintf(
            "terminus connection:info %s.dev --field=mysql_command",
            $this->getDestinationDirectory()->getSiteInfo()->getName()
        );
        // terminus connection:set hivinsite-2021.dev git
        // cd local-copies/hivinsite
        // git add *
        // git commit -m 'd9ify conversion'

        $output->writeln("===> TODO: Check-in Version-managed files and push.");
    }

    /**
     * @step Set Destination directory
     * @description
     * Destination name will be {source}-{THIS YEAR} by default
     * if you don't provide a value.
     *
     * @param Directory $destinationDirectory
     */
    public function setDestinationDirectory(Directory $destinationDirectory): void
    {
        $this->destinationDirectory = $destinationDirectory;
    }

    /**
     * @return Directory
     */
    public function getDestinationDirectory(): Directory
    {
        return $this->destinationDirectory;
    }
}
