<?php

namespace Pantheon\Terminus\Helpers\Site;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\Local\CloneCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Helpers\Composer\ComposerFile;
use Pantheon\Terminus\Exceptions\ComposerInstallException;
use Pantheon\Terminus\Helpers\Traits\CommandExecutorTrait;
use Pantheon\Terminus\Helpers\Traits\DefaultClonePathTrait;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\IO;
use Robo\Contract\IOAwareInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class Directory
 *
 * @package D9ify\Site
 */
class Directory implements ContainerAwareInterface, IOAwareInterface, SiteAwareInterface, LoggerAwareInterface
{

    use CommandExecutorTrait;
    use DefaultClonePathTrait;
    use ContainerAwareTrait;
    use IO;
    use SiteAwareTrait;
    use LoggerAwareTrait;

    /**
     * @var
     */
    protected $site;

    /**
     * @var string
     */
    protected string $clonePath;

    /**
     * @var ComposerFile
     */
    protected ?ComposerFile $composerFile = null;

    /**
     * Directory constructor.
     *
     * @param Site|string $site
     *
     * @throws \JsonException
     */
    public function __construct($site)
    {
        $this->setSource($site);
    }

    /**
     * @param $dir
     *
     * @return bool Success/Failure.
     */
    public static function delTree($dir): bool
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? static::delTree("$dir/$file") : unlink("$dir/$file");
            }
            return rmdir($dir);
        }
        return true;
    }

    /**
     * @throws \Exception
     */
    public function ensureLocalCopyOfRepo(bool $create = false)
    {
        $valid = $this->getSource()->valid() ?? false;
        if ($valid === false) {
            // if site doesn't exist
            if ($create === true) {
                $valid = $this->getSource()->create();
            }
            if ($valid === false) {
                throw new \Exception("Site does not exist and cannot be created.");
            }
        }
        $cloneCommand = new CloneCommand();
        $this->getContainer()->inflect($cloneCommand);
        $clonePath = $cloneCommand->clone($this->getSource());
        $this->logger->info("Clone Path:" . $clonePath);
        $this->setClonePath($clonePath);
        $this->execute(
            \sprintf("rm -Rf %s/vendor", $this->getClonePath())
        );
        $this->setComposerFile();
    }



    /**
     * @throws \Exception
     */
    public function setComposerFile()
    {
        $this->composerFile = new ComposerFile($this->getComposerFileExpectedPath());
    }

    /**
     * @return string
     */
    private function getComposerFileExpectedPath()
    {
        return sprintf("%s/composer.json", $this->getClonePath());
    }

    /**
     * @return ComposerFile
     */
    public function &getComposerObject(): ?ComposerFile
    {
        return $this->composerFile;
    }

    /**
     * @param $regex
     *
     * @return \SplFileInfo[]
     */
    public function spelunkFilesFromRegex($regex): array
    {
        $this->output()->writeln(sprintf("Searching files for regex: %s", $regex));
        $allFiles = iterator_to_array(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->getClonePath())
            )
        );
        $max = count($allFiles);
        $current = 0;
        return array_filter($allFiles, function (\SPLFileInfo $file) use (
            $regex,
            &$max,
            &$current,
            &
            $output
        ) {
            $this->progressBar($current++, $max, $output);
            return preg_match(
                $regex,
                $file->getRealPath()
            ) && !strpos(
                $file->getRealPath(),
                'test'
            );
        });
    }

    /**
     * @param $done
     * @param $total
     */
    protected function progressBar($done, $total)
    {
        $perc = floor(($done / $total) * 100);
        $left = 100 - $perc;
        $write = sprintf(
            "\033[0G\033[2K[%'={$perc}s>%-{$left}s] - $perc%% - $done/$total",
            "",
            ""
        );
        $this->output->write($write);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function install(): array
    {
        is_file($this->clonePath . "/composer.lock") ?
            unlink($this->clonePath . "/composer.lock") : [];
        $result = $this->execute("rm -Rf %s && cd %s && composer upgrade --with-dependencies", [
            $this->clonePath . "/vendor",
            $this->clonePath
        ]);
        if ($this->lastStatus !== 0) {
            throw new ComposerInstallException($result, $this->output());
        }
        return $result;
    }

    /**
     * @return \SplFileInfo
     */
    public function getClonePath(): ?string
    {
        return $this->clonePath;
    }

    /**
     * @param string $clonePath
     */
    public function setClonePath(string $clonePath): void
    {
        $this->clonePath = $clonePath;
    }

    /**
     * Add "custom" code folders in modules and themes.
     */
    public function ensureCustomCodeFoldersExist()
    {
        $this->output->write(PHP_EOL);
        $custom = $this->getClonePath() . "/web/themes/custom";
        $this->output->writeln(sprintf('Ensure custom theme folder exists: %s', $custom));
        if (!file_exists($custom)) {
            mkdir(
                $custom,
                0777,
                true
            );
        }
        $custom = $this->getClonePath() . "/web/modules/custom";
        $this->output->writeln(
            sprintf('Ensure custom modules folder exists: %s', $custom)
        );
        if (!file_exists($custom)) {
            mkdir(
                $custom,
                0777,
                true
            );
        }
    }

    /**
     * @return string
     */
    public function getDefaultClonePathBase()
    {
        // Get path resoltion from default composer file directory
        return $_SERVER['HOME'] . "/pantheon-local-copies";
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function ensurePantheonYamlValues(InputInterface $input)
    {
        $pantheonYaml = [];
        $yamlFile = $this->clonePath . "/pantheon.yaml";
        if (is_file($yamlFile)) {
            $pantheonYaml = yaml_parse_file($yamlFile);
        }
        $pantheonYaml["database"]['version'] = "10.4";
        $pantheonYaml["build_step"] = true;
        $pantheonYaml["drush_version"] = 10;
        $pantheonYaml['protected_web_paths'] = [
           "/private/",
           "/sites/default/files/private/",
            "/sites/default/files/config/"
        ];
        $this->output->writeln([
            "Updating Pantheon.yaml file in destination directory:",
            print_r($pantheonYaml, true)
        ]);
        yaml_emit_file($yamlFile, $pantheonYaml);
    }

    /**
     * @return \Pantheon\Terminus\Models\Site|null
     */
    public function getSource(): ? Site
    {
        return $this->site ?? null;
    }


    /**
     * @param string|Site $site
     */
    public function setSource($site)
    {
        if (is_string($site)) {
            $this->site = $this->getSite($site);
        }
        if ($site instanceof Site) {
            $this->site = $site;
        }
        if (is_object($site)) {
            $site = (array)$site;
        }
        if (is_array($site) && isset($site['id'])) {
            $this->site = $this->getSite($site['id']);
        }
        if (is_array($site) && isset($site['site'])) {
            $this->site = $site['site'];
        }
        if (!$this->site instanceof Site) {
            \Kint::dump($site);
            throw new TerminusNotFoundException("Site not found: ");
        }
    }
}
