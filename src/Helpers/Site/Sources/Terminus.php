<?php

namespace Pantheon\D9ify\Site\Sources;

use CzProject\GitPhp\GitRepository;
use Pantheon\D9ify\Traits\CommandExecutorTrait;
use Pantheon\D9ify\Traits\DefaultClonePathTrait;
use Pantheon\D9ify\Traits\SiteInfoTrait;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Terminus
 * @package D9ify\Site\Sources
 */
class Terminus implements SiteSourceInterface
{

    use CommandExecutorTrait;
    use SiteInfoTrait;
    use DefaultClonePathTrait;

    /**
     * @var string
     */
    protected string $siteID;
    /**
     * @var string|mixed
     */
    protected string $referenceEnvironment;

    /**
     * Terminus constructor.
     * @param $siteID
     * @param string $referenceEnvironment
     */
    public function __construct($siteID, $referenceEnvironment = "live")
    {
        $this->setSiteInfoFromSiteId($siteID);
        $this->referenceEnvironment = $referenceEnvironment;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return $this->getSiteInfo()->valid();
    }

    /**
     * @return array
     */
    public function getConnectionInfo(): array
    {
        return $this->execute("terminus connection:info %s.dev --format=json", [
            $this->getSiteInfo()->getName()
        ]);
    }

    /**
     * @return string
     */
    protected function getClonePath():string
    {
        return $this->getDefaultClonePathBase()  . DIRECTORY_SEPARATOR .  $this->getSiteInfo()->getName();
    }

    /**
     * @return string
     */
    protected function getGitCommand():string
    {
        return str_replace(
            $this->getSiteInfo()->getName(),
            $this->getClonePath(),
            $this->getConnectionInfo()['git_command']
        );
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function cloneFiles(OutputInterface $output): bool
    {

        $this->execute($this->getGitCommand());
        if ($this->getLastStatus() !== 0) {
            throw new \Exception("Cannot clone site with terminus command." .
                join(PHP_EOL, $this->execResult));
        }
        $output->writeln(
            sprintf(
                "Site Code Folder: %s",
                $this->clonePath->getRealPath()
            )
        );
    }
}
