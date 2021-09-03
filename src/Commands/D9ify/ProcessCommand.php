<?php declare(strict_types=1);

namespace Pantheon\Terminus\Commands\D9ify;

use Pantheon\Terminus\Commands\Local\GetLiveDBCommand;
use Pantheon\Terminus\Commands\Local\GetLiveFilesCommand;
use Pantheon\Terminus\Commands\Site\CreateCommand;
use Pantheon\Terminus\Commands\Site\InfoCommand;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Helpers\Site\Directory;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Robo\Common\IO;
use Robo\Contract\ConfigAwareInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @name d9ify
 * Class ProcessCommand
 *
 * @package Pantheon\Terminus\Commands\D9ify
 */
class ProcessCommand extends TerminusCommand implements SiteAwareInterface, ConfigAwareInterface
{
    use SiteAwareTrait;
    use ConfigAwareTrait;
    use IO;
    use WorkflowProcessingTrait;

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
     * @var Directory
     */
    protected Directory $sourceDirectory;
    /**
     * @var Directory
     */
    protected Directory $destinationDirectory;

    /**
     * Clone a pantheon site and spelunk the contents to create new D9 site.
     *
     * @authorize
     *
     * @command d9ify:process
     *
     * @aliases d9p
     *
     * @param string $sourceSite
     *   Pantheon Site name/ID.
     * @param ?string $destinationSite
     *   Pantheon Destination Site (optional)
     *
     * @return void
     * @throws \JsonException
     *
     * @usage terminus d9ify:process {sourceSiteName}
     * @usage terminus d9ify:process {sourceSiteName} {destinationSiteName}
     */
    public function process($sourceSite, $destinationSite = null, $options = [])
    {
        $this->output()->writeln(static::$HELP_TEXT);
        try {
            // Handle Source Site.
            if ($sourceSite instanceof Site) {
                $sourceSiteObject = $sourceSite;
                $sourceSite = $sourceSiteObject->getName();
            }
            if (is_string($sourceSite)) {
                $sourceSiteObject = $this->getSite($sourceSite);
            }
            if (isset($sourceSiteObject) && $sourceSiteObject instanceof Site) {
                $this->sourceDirectory = new Directory(['site' => $sourceSiteObject]);
                $this->getContainer()->inflect($this->sourceDirectory);
            }

            // Create destination Site if not exists or value is null.
            $destinationSiteObject = null;
            if ($destinationSite == null) {
                $destinationSite = $sourceSiteObject->getName() . "-" . date("Y");
            }
            if ($destinationSite instanceof Site) {
                $destinationSiteObject = $destinationSite;
                $destinationSite = $destinationSiteObject->getName();
            }
            if (is_string($destinationSite)) {
                $results = $this->sites()->nameIsTaken($destinationSite);
                if ($results === false) {
                    $this->sites()->create([
                       $destinationSite,
                       $destinationSite,
                       "drupal9"
                    ]);
                }

                if ($results) {
                    $destinationSiteObject = $this->sites()->get($destinationSite);
                }
            }

            if (!isset($destinationSiteObject) || !$destinationSiteObject instanceof Site) {
                $createCommand = new CreateCommand();
                $this->getContainer()->inflect($createCommand);
                $createCommand->create(
                    $destinationSite,
                    $destinationSite,
                    'drupal9',
                    ['org' => null]
                );
                $destinationSiteInfoCommand = $this->getContainer()->get(InfoCommand::class);
                $destinationSiteInfo = $destinationSiteInfoCommand->info($destinationSite)->getArrayCopy();
                $destinationSiteObject = new Site($destinationSiteInfo);
                $this->getContainer()->inflect($destinationSiteObject);
            }
            if (isset($destinationSiteObject) && $destinationSiteObject instanceof Site) {
                $this->destinationDirectory = new Directory(['site' => $destinationSiteObject ]);
                $this->getContainer()->inflect($this->destinationDirectory);
            }

            if (!isset($this->sourceDirectory) || !isset($this->destinationDirectory)) {
                throw new TerminusException("Cannot instantiate source/destination");
            }

            $this->output()->writeln([
                PHP_EOL,
                PHP_EOL,
                PHP_EOL,
                "*************************************************************",
                PHP_EOL,
                sprintf(
                    "Source Site: %s (%s) ",
                    $sourceSiteObject->getName(),
                    $sourceSiteObject->get('id')
                ),
                PHP_EOL,
                sprintf(
                    "Destination Site: %s (%s) ",
                    $destinationSiteObject->getName(),
                    $destinationSiteObject->get('id')
                ),
                PHP_EOL,
                "*************************************************************",
            ]);
            $this->copyRepositoriesFromSource();
            $this->updateDestModulesAndThemesFromSource();
            $this->updateDestEsLibrariesFromSource();
            $this->writeComposer();
            $this->destinationComposerInstall();
            $this->copyCustomCode();
            $this->copyConfigFiles();
            $this->downloadDatabase();
            $this->downloadSourceSiteFilesDirectory();
        } catch (D9ifyExceptionBase $d9ifyException) {
            // TODO: Composer install exception help text
            $this->output()->writeln((string)$d9ifyException);
        } catch (\Exception $e) {
            // TODO: General help text and how to restart the process
            $this->output()->writeln("Script ended in Exception state. " . $e->getMessage());
            $this->output()->writeln($e->getTraceAsString());
        } catch (\Throwable $t) {
            // TODO: General help text and how to restart the process
            $this->output()->write("Script ended in error state. " . $t->getMessage());
            $this->output()->writeln($t->getTraceAsString());
        }
    }

    /**
     * @return Directory
     */
    protected function getSourceDirectory(): Directory
    {
        return $this->sourceDirectory;
    }

    /**
     * @step Set Source directory
     * @description
     * Source Param is not optional and needs to be
     * a pantheon site ID or name.
     *
     * @param Directory $sourceDirectory
     */
    protected function setSourceDirectory(Directory $sourceDirectory): void
    {
        $this->sourceDirectory = $sourceDirectory;
    }

    /**
     * @step Clone Source & Destination.
     * @description
     * Clone both sites to folders inside this root directory.
     * If destination does not exist, create the using Pantheon's
     * Terminus API. If destination doesn't exist, Create it.
     *
     */
    protected function copyRepositoriesFromSource()
    {
        $this->output()->writeln([
            "===> Ensuring source and destination folders exist.",
            PHP_EOL,
            "*********************************************************************",
            "**     If you've never accessed the site before you may be         **",
            "**  asked to accept the site's fingerprint. Type 'yes' when asked  **",
            "*********************************************************************",
            PHP_EOL,
        ]);
        $this->getSourceDirectory()->ensureLocalCopyOfRepo(false);
        $this->getDestinationDirectory()->ensureLocalCopyOfRepo(true);
        $this->destinationDirectory->getComposerObject()->setRepositories(
            $this->sourceDirectory->getComposerObject()->getOriginal()['repositories'] ?? []
        );
        $this->output()->writeln([
            "*********************************************************************",
            sprintf("Source Folder: %s", $this->getSourceDirectory()->getClonePath()),
            sprintf("Destination Folder: %s", $this->getDestinationDirectory()->getClonePath()),
            "*********************************************************************",
        ]);
    }

    /**
     * @return Directory
     */
    protected function getDestinationDirectory(): Directory
    {
        return $this->destinationDirectory;
    }

    /**
     * @step Set Destination directory
     * @description
     * Destination name will be {source}-{THIS YEAR} by default
     * if you don't provide a value.
     *
     * @param Directory $destinationDirectory
     */
    protected function setDestinationDirectory(Directory $destinationDirectory): void
    {
        $this->destinationDirectory = $destinationDirectory;
    }

    /**
     * @step Move over Contrib
     * @description
     * Spelunk the old site for MODULE.info.yaml and after reading
     * those files. This step searches for every {modulename}.info.yml. If that
     * file has a 'project' proerty (i.e. it's been thru the automated services at
     * drupal.org), it records that property and version number and ensures
     * those values are in the composer.json 'require' array. Your old composer
     * file will re renamed backup-*-composer.json.
     *
     *
     * @regex
     * [REGEX](https://regex101.com/r/60GonN/1)
     *
     * Get every .info.y{a}ml file in source.
     */
    protected function updateDestModulesAndThemesFromSource()
    {
        $this->output()->writeln("===> Updating Getting Modules and Themes from source.");
        $infoFiles = $this->sourceDirectory->spelunkFilesFromRegex('/(\.info\.yml|\.info\.yaml?)/');
        $toMerge = [];
        $composerFile = $this->getDestinationDirectory()
            ->getComposerObject();
        foreach ($infoFiles as $fileName => $fileInfo) {
            if ($fileInfo->isFile()) {
                $contents = file_get_contents($fileName);
                preg_match('/project\:\ ?\'(.*)\'$/m', $contents, $projectMatches);
                preg_match('/version\:\ ?\'(.*)\'$/m', $contents, $versionMatches);
                if (is_array($projectMatches) && isset($projectMatches[1])) {
                    $composerFile->addRequirement(
                        "drupal/" . $projectMatches[1],
                        "^" . str_replace("8.x-", "", $versionMatches[1])
                    );
                }
            }
        }
        $this->output()->write(PHP_EOL);
        $this->output()->write(PHP_EOL);
        $this->output()->writeln([
            "*******************************************************************************",
            "* Found new Modules & themes from the source site:                            *",
            "*******************************************************************************",
            print_r($composerFile->getDiff(), true),
        ]);
        return 0;
    }


    /**
     * @step JS contrib/drupal libraries
     * @description
     * Process /libraries folder if exists & Add ES Libraries to the composer
     * install payload.
     *
     * @throws \JsonException
     *
     * @regex
     * [REGEX](https://regex101.com/r/EHYzcz/1)
     *
     * Get every package.json in the libraries folder.
     *
     */
    protected function updateDestEsLibrariesFromSource()
    {
        $this->output()->writeln("===> Updating ES /libraries from source directory");
        $fileList = $this->sourceDirectory->spelunkFilesFromRegex('/libraries\/[0-9a-z-]*\/(package\.json$)/');
        $repos = $this->sourceDirectory->getComposerObject()->getOriginal()['repositories'];
        $composerFile = $this->getDestinationDirectory()->getComposerObject();
        foreach ($fileList as $key => $file) {
            try {
                $package = \json_decode(
                    file_get_contents($file->getRealPath()),
                    true,
                    10,
                    JSON_THROW_ON_ERROR
                );
            } catch (\JsonException $jsonException) {
                continue;
            }

            $repoString = (string)$package['name'];
            if (empty($repoString)) {
                $repoString = is_string($package['repository']) ?
                    $package['repository'] : $package['repository']['url'];
            }
            if (empty($repoString) || is_array($repoString)) {
                $this->output()->writeln([
                    "*******************************************************************************",
                    "* Skipping the file below because the package.json file does not have         *",
                    "* a 'name' or 'repository' property. Add it by hand to the composer file.     *",
                    "* like so: \"npm-asset/{npm-registry-name}\": \"{Version Spec}\" in           *",
                    "* the REQUIRE section. Search for the id on https://www.npmjs.com             *",
                    "*******************************************************************************",
                    $file->getRealPath(),
                ]);
                continue;
            }
            $array = explode(DIRECTORY_SEPARATOR, $repoString);
            $libraryName = @array_pop($array);
            if (isset($repos[$libraryName])) {
                $composerFile->addRequirement(
                    $repos[$libraryName]['package']['name'],
                    $repos[$libraryName]['package']['version']
                );
                continue;
            }
            if ($libraryName !== "") {
                // Last ditch guess:
                $composerFile->addRequirement("npm-asset/" . $libraryName, "^" . $package['version']);
            }
        }
        $installPaths = $composerFile->getExtraProperty('installer-paths');
        if (!isset($installPaths['web/libraries/{$name}'])) {
            $installPaths['web/libraries/{$name}'] = [];
        }
        $installPaths['web/libraries/{$name}'] = array_unique(
            array_merge($installPaths['web/libraries/{$name}'] ?? [], [
                "type:bower-asset",
                "type:npm-asset",
            ])
        );

        $composerFile->setExtraProperty('installer-paths', $installPaths);
        $installerTypes = $composerFile->getExtraProperty('installer-types') ?? [];
        $composerFile->setExtraProperty(
            'installer-types',
            array_unique(
                array_merge($installerTypes, [
                    "bower-asset",
                    "npm-asset",
                    "library",
                ])
            )
        );
        $this->output()->write(PHP_EOL);
        $this->output()->write(PHP_EOL);
        $this->output()->writeln([
            "*******************************************************************************",
            "* Found new ESLibraries from the source site:                                 *",
            "*******************************************************************************",
            print_r($composerFile->getDiff(), true),
        ]);
    }

    /**
     * @step Write the composer file.
     * @description
     * Write the composer file to disk.
     *
     *
     * @return int|mixed
     */
    protected function writeComposer()
    {
        $this->output()->writeln("===> Writing Composer file to destination");

        $this->output()->writeln([
            "*********************************************************************",
            "* These changes are being applied to the destination site composer: *",
            "*********************************************************************",
        ]);
        $this->getDestinationDirectory()
            ->getComposerObject()
            ->addRequirement("drupal/core", "^9.1");
        $this->output()->writeln(print_r($this->destinationDirectory
            ->getComposerObject()
            ->getDiff(), true));

        $this->destinationDirectory
            ->getComposerObject()
            ->backupFile();
        $response = "y";
        if ($this->input()->isInteractive()) {
            $response = $this->ask(sprintf(
                "Write these changes to the composer file at %s? (y/n)",
                $this->destinationDirectory
                    ->getComposerObject()
                    ->getRealPath()
            ));
        }

        if ("y" == strtolower($response)) {
            $this->getDestinationDirectory()
                ->getComposerObject()
                ->write();
            $this->output()->writeln("===> Composer File Written");
            return 0;
        }
        $this->output()->writeln("===> The composer File was not changed");
        return 0;
    }

    /**
     * @step composer install
     * @description
     * Exception will be thrown if install fails.
     *
     */
    protected function destinationComposerInstall()
    {
        $this->getDestinationDirectory()
            ->install();
    }

    /**
     * @step Copy Custom Code
     * @description
     * This step looks for {MODULENAME}.info.yml files that also have "custom"
     * in the path. If they have THEME in the path it copies them to web/themes/custom.
     * If they have "module" in the path, it copies the folder to web/modules/custom.
     *
     *
     * @return bool
     *
     * @regex
     * [REGEX](https://regex101.com/r/kUWCou/1)
     *
     * get every .info file with "custom" in the path, e.g.
     *
     * |---|----------------------------------------------------------*--|
     * | ✓ | web/modules/custom/milken_migrate/milken_migrate.info.yaml |
     * | ✗ | web/modules/contrib/entity_embed/entity_embed.info.yaml    |
     * | ✓ | web/modules/custom/milken_base/milken_base.info.yaml       |
     *
     */
    protected function copyCustomCode(): bool
    {
        $this->output()->writeln("===> Copying Custom Code");

        $failure_list = [];

        $infoFiles = $this
            ->sourceDirectory
            ->spelunkFilesFromRegex('/custom\/[0-9a-z-_]*\/[0-9a-z-_]*(\.info\.yml|\.info\.yaml?)/');
        $this->getDestinationDirectory()->ensureCustomCodeFoldersExist();
        foreach ($infoFiles as $fileName => $fileInfo) {
            try {
                $contents = Yaml::parse(file_get_contents($fileName));
            } catch (\Exception $exception) {
                if ($this->output()->isVerbose()) {
                    $this->output()->writeln($exception->getTraceAsString());
                }
                continue;
            }

            // Skip any info file that has the "project" setting because it will be in contrib.
            // Skip any info file that doesn't have a "type" setting.
            if (!isset($contents['type'])) {
                continue;
            }
            $sourceDir = dirname($fileInfo->getRealPath());
            switch ($contents['type']) {
                case "module":
                    $destination = $this->getDestinationDirectory()->getClonePath() . "/web/modules/custom";
                    break;

                case "theme":
                    $destination = $this->getDestinationDirectory()->getClonePath() . "/web/themes/custom";
                    break;

                default:
                    continue 2;
            }

            $command = sprintf(
                "cp -Rf %s %s",
                $sourceDir,
                $destination
            );
            if ($this->output()->isVerbose()) {
                $this->output()->writeln($command);
            }

            exec(
                $command,
                $result,
                $status
            );
            if ($status !== 0) {
                $failure_list[$fileName] = $result;
            }
            if (!isset($contents['core'])) {
                $contents['core'] = "^9";
            }
            // If the module does not have d9 in the "core" module value, add it
            if (isset($contents['core']) && strpos($contents['core'], "9") === false) {
                $contents['core'] .= "| ^9";
                file_put_contents($fileName, Yaml::dump($contents, 1, 5));
            }
        }
        $this->output()->write(PHP_EOL);
        $this->output()->write(PHP_EOL);
        $failures = count($failure_list);
        $this->output()->writeln(sprintf("Copy operations are complete with %d errors.", $failures));
        if ($failures) {
            $toWrite = [
                    "*******************************************************************************",
                    "* The following files had an error while copying. You will need to inspect    *",
                    "* The folders by hand or diff them. I'm not saying the folders weren't copied.*",
                    "* I'm saying I'm not sure they were copied in-tact. Double check the contents *",
                    "* They might have errored on a single file which would stop the copy.         *",
                    "* If you want to see the complete output from the copies, run this command    *",
                    "* with the --verbose switch.                                                  *",
                    "*******************************************************************************",
                ] + array_keys($failure_list);
            $this->output()->writeln($toWrite);
            if ($this->output()->isVerbose()) {
                $this->output()->write(print_r($failure_list, true));
            }
        }
        $this->output()->writeln("===> Done Copying Custom Code");

        return true;
    }

    /**
     * @step Ensure pantheon.yaml has preferred values
     * @description
     * Write known values to the pantheon.yml file.
     *
     *
     * @extra
     * [REGEX](https://regex101.com/r/vWIStG/1)
     *
     *  Try to find the config directory based on the system.site.yml
     */
    protected function copyConfigFiles()
    {
        $this->output()->writeln("===> Copying Config Files");

        $configFiles = $this->getSourceDirectory()
            ->spelunkFilesFromRegex(
                '/[!^core]\/(system\.site\.yml$)/',
                $this->output()
            );
        $configDirectory = @dirname(
            (string) reset($configFiles)
        ) ?? null;

        if ($configDirectory === null) {
            $this->output()->writeln([
                "A config directory was not found for this site. ",
                "Expectation was that it was in `{site root directory}/config`",
            ]);
            return false;
        }
        exec(
            sprintf(
                "mkdir -p %s",
                $this->getDestinationDirectory()->getClonePath() . "/config/sync"
            ),
            $result,
            $status
        );
        exec(
            sprintf(
                "cp -R %s %s",
                $configDirectory . "/*.yml",
                $this->getDestinationDirectory()->getClonePath() . "/config/sync"
            ),
            $result,
            $status
        );
        if ($status !== 0) {
            $this->output()->writeln($result);
        }
        $this->output()->writeln([
            PHP_EOL,
            "===> Done Copying Config Files",
        ]);
        return true;
    }

    /**
     * @step Create a backup of the site database and download.
     * @description
     * Download a copy of the latest version of the database.
     *
     * | WARNING                                                                     |
     * |-----------------------------------------------------------------------------|
     * | If pantheon terminus integration is setup incorrectly on this machine,      |
     * | this step will fail.                                                        |
     *
     *
     */
    protected function downloadDatabase()
    {
        $downloadDbCommand = new GetLiveDBCommand();
        $this->getContainer()->inflect($downloadDbCommand);
        $downloadDbCommand->downloadLiveDbBackup($this->getSourceDirectory()->getSource());
    }

    /**
     * @step Download backup of source files
     * @description
     * Using terminus, get a copy of the sites/default/files folder
     *
     * | WARNING                                                                     |
     * |-----------------------------------------------------------------------------|
     * | We're downloading a backup rather than rsyncing from the source.            |
     * | This is going to have a tendency to be faster with site archives > 1gb      |
     *
     */
    protected function downloadSourceSiteFilesDirectory()
    {
        $downloadFilesCommand = new GetLiveFilesCommand();
        $this->getContainer()->inflect($downloadFilesCommand);
        $downloadFilesCommand->downloadLiveFilesBackup($this->getSourceDirectory()->getSource());
    }
}
