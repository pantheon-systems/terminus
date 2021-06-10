<?php

namespace Pantheon\Terminus\Helpers\Site;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Helpers\Composer\ComposerFile;
use Pantheon\Terminus\Exceptions\ComposerInstallException;
use Pantheon\Terminus\Helpers\Traits\CommandExecutorTrait;
use Pantheon\Terminus\Helpers\Traits\DefaultClonePathTrait;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Robo\Common\IO;
use Robo\Contract\IOAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Directory
 *
 * @package D9ify\Site
 */
class Directory implements ContainerAwareInterface, IOAwareInterface, SiteAwareInterface
{

    use CommandExecutorTrait;
    use DefaultClonePathTrait;
    use ContainerAwareTrait;
    use IO;
    use SiteAwareTrait;

    protected $site;

    /**
     * @var \SplFileInfo
     */
    protected $clonePath;

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
        if (is_string($site)) {
            $site = $this->getSite($site);
        }
        $this->site = $site;
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
    public function ensure(bool $create = false)
    {
        $valid = $this->getSource()->valid();
        if ($valid === false) {
            // if site doesn't exist
            if ($create === true) {
                $valid = $this->getSource()->create();
            }
            if ($valid === false) {
                throw new \Exception("Site does not exist and cannot be created.");
            }
        }
        $this->clonePath = new \SplFileInfo(
            $this->getDefaultClonePathBase() .
            DIRECTORY_SEPARATOR . $this->getSource()->getSiteInfo()->getName()
        );
        if (!$this->clonePath->isDir()) {
            // -oStrictHostKeyChecking=no
            $this->getSource()->cloneFiles($this->getOutput());
        }

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
    public function spelunkFilesFromRegex($regex, OutputInterface $output): array
    {
        $output->writeln(sprintf("Searching files for regex: %s", $regex));
        $allFiles = iterator_to_array(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->clonePath)
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
    protected function progressBar($done, $total, OutputInterface $output)
    {
        $perc = floor(($done / $total) * 100);
        $left = 100 - $perc;
        $write = sprintf(
            "\033[0G\033[2K[%'={$perc}s>%-{$left}s] - $perc%% - $done/$total",
            "",
            ""
        );
        $output->write($write);
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function install()
    {
        is_file($this->clonePath . "/composer.lock") ?
            unlink($this->clonePath . "/composer.lock") : [];
        $this->execute("rm -Rf %s && cd %s && composer upgrade --with-dependencies", [
            $this->clonePath . "/vendor",
            $this->clonePath
        ]);
        if ($this->execResult[0] !== 0) {
            throw new ComposerInstallException($result, $output);
        }
        return $result;
    }

    /**
     * @return \SplFileInfo
     */
    public function getClonePath(): \SplFileInfo
    {
        return $this->clonePath;
    }

    /**
     * @param \SplFileInfo $clonePath
     */
    public function setClonePath(\SplFileInfo $clonePath): void
    {
        $this->clonePath = $clonePath;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function ensureCustomCodeFoldersExist(InputInterface $input, OutputInterface $output)
    {
        $output->write(PHP_EOL);
        $custom = $this->getClonePath() . "/web/themes/custom";
        $output->writeln(sprintf('Ensure custom theme folder exists: %s', $custom));
        if (!file_exists($custom)) {
            mkdir(
                $custom,
                0777,
                true
            );
        }
        $custom = $this->getClonePath() . "/web/modules/custom";
        $output->writeln(
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

    public function getDefaultClonePathBase()
    {
        // Get path resoltion from default composer file directory
        return dirname(\Composer\Factory::getComposerFile()) . "/local-copies";
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function ensurePantheonYamlValues(InputInterface $input, OutputInterface $output)
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
        $output->writeln([
            "Updating Pantheon.yaml file in destination directory:",
            print_r($pantheonYaml, true)
        ]);
        yaml_emit_file($yamlFile, $pantheonYaml);
    }

    public function getInfo() : Site
    {
        return $this->getSite($this->siteID);
    }
}
