<?php

/**
 * Automate common housekeeping tasks.
 *
 * Class RoboFile
 */
class RoboFile extends \Robo\Tasks
{

  /**
   * Stand up a docker instance from the site docker-compose.yml file
   */
  public function dockerUp()
  {
    $this->taskExec('docker-compose up -d')->run();
  }

  /**
   * Bring down all containers in the docker-compose.yml
   */
  public function dockerDown()
  {
    $this->taskExec('docker-compose down')->run();
  }

  /**
   * Clean the cruft from your docker installation
   */
  public function dockerClean()
  {
    $this->taskExec('docker system prune -f')->run();
    $this->taskExec('docker container prune  -f')->run();
    $this->taskExec('docker image prune -f')->run();
    $this->taskExec('docker network prune -f')->run();
    $this->taskExec('docker volume prune -f')->run();
  }

  /**
   * run-clone-livedb: env ## clone the live DB to the docker mysql instance
   * @echo '** If terminus is set up incorrectly on this machine, the database download will fail. **'
   * @[[ -f "./db/${BACKUP_FILE_NAME}" ]] && rm "./db/${BACKUP_FILE_NAME}" || true
   * terminus backup:create ${LIVE_SITE} --element=database --yes > /dev/null
   * terminus backup:get ${LIVE_SITE} --element=database --yes --to="./db/${BACKUP_FILE_NAME}" > /dev/null
   * [[ -f "db/${BACKUP_FILE_NAME}" ]] && make run-clone-restore && true
   *
   * run-clone-livefiles:  ## YOUR SSH KEY MUST BE REGISTERED WITH PANTHEON AND SHARED WITH THE DOCKER CONTAINER FOR THIS TO WORK
   * @echo '**If your SSH key is not registered with Pantheon, this will fail.**'
   * SFTP_COMMAND=$(shell terminus connection:info ${PANTHEON_SITE_NAME}.live --format=json | jq -r '.sftp_command') > /dev/null
   * SSH_COMMAND=${SFTP_COMMAND}/sftp -o Port=/ssh -p /
   * FILES_FOLDER=`realpath db/files`
   * FILES_SYMLINK=`realpath web/sites/default`
   * rsync -rvlz --copy-unsafe-links --size-only --checksum --ipv4 --progress -e ${SFTP_COMMAND}:files/ ${FILES_FOLDER}
   * rm -Rf web/sites/default/files
   * ln -s ${FILES_FOLDER} ${FILES_SYMLINK}
   */


  /**
   * Install drupal using a profile.
   *
   * @param string $profile 'demo_umami'
   *
   * @throws \Exception
   */
  public function siteInstall(string $profile = 'demo_umami')
  {
    // TODO: wait for mysql service to be avail in container
    $project = getenv('PROJECT_NAME');
    $container = "{$project}-php";
    $this->stopOnFail(true);
    $this->confirm(
      "Type 'y' to erase the database in the docker container and re-install drupal with the '{$profile}' profile"
    );
    $this->taskExec('rm -Rf web/sites/default/files web/sites/default/temp web/sites/default/private')
      ->run();
    $this->waitForContainer("{$project}-mysql");
    $this->taskDockerExec($container)
      ->interactive(true)
      ->exec("drush site:install --account-name=demo --site-name={$project} --locale=en --yes  {$profile}")
      ->run();
    $this->siteEnableModules([
      'redis',
      'search_api',
      'search_api_solr',
      'search_api_pantheon',
      'search_api_solr_admin',
    ]);
  }

  /**
   * @param string $container
   * @param int $retries
   *
   * @throws \Exception
   */
  protected function waitForContainer(string $container, $retries = 10)
  {
    $iterations = 0;
    $status = $this->getContainerHealth($container);
    while ($status != 'healthy') {
      sleep(10);
      if ($iterations >= $retries) {
        throw new \Exception(
          "Service {$container} was not available after {$retries} retries"
        );
      }
      $iterations += 1;
      $status = $this->getContainerHealth($container);
    }
  }

  /**
   * @param string $container
   *
   * @return string|null
   */
  protected function getContainerHealth(string $container): ? string
  {
    $response = shell_exec(
      "docker inspect {$container} | jq -r '.[].State.Health.Status'"
    );
    return trim(str_replace(PHP_EOL, '', $response));
  }

  /**
   * @param array|string[] $modules
   *
   * @return Robo\Result
   */
  protected function siteEnableModules(array $modules = ['redis'])
  {
    return $this->dockerDrush('pm-enable --yes ' . join(' ', $modules));
  }

  /**
   * @param string $drushCommand
   * @param string $container 'php'
   *
   * @return \Robo\Result
   */
  public function dockerDrush(string $drushCommand = 'site:status', string $container = 'php')
  {
    return $this->taskDockerExec(getenv('PROJECT_NAME') . '-' . $container)
      ->interactive(true)
      ->exec('drush ' . $drushCommand)
      ->run();
  }

  /**
   * @param string $siteUser
   *
   */
  public function siteLogin(string $siteUser = 'admin')
  {
    $url = (string)$this->dockerDrush('uli ' . $siteUser)
      ->getOutputData();
    $this->taskOpenBrowser($url);
  }


  public function copyBackTemplates()
  {
    $templateDir = getenv('HOME') . '/Projects/terminus/templates/localdev';
    copy(__DIR__ . '/docker-compose.yml',  $templateDir . "/docker-compose.yml");
    copy(__DIR__ . '/RoboFile.php',  $templateDir . "/RoboFile.php" );
    copy(__DIR__ . '/web/sites/default/settings.local.php',  $templateDir . "/settings.local.php" );
    copy(__DIR__ . '/.envrc', $templateDir . '/.envrc');
  }

}
