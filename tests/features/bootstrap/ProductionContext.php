<?php

namespace Pantheon\Terminus\FeatureTests;

use Behat\Behat\Context\Context;
use Behat\Behat\Tester\Exception\PendingException;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class ProductionContext
 * Features context for Behat feature testing
 * @package Pantheon\Terminus\FeatureTests
 */
class ProductionContext implements Context
{
    public $cliroot = '';
    private $cache_token_dir;
    private $environment_variables = [];
    private $fixtures_dir;
    private $plugin_dir;
    private $plugin_dir_name;
    private $parameters;
    private $output;
    private $start_time;

    const DEFAULT_PLUGIN_DIR_NAME = 'default';
    const MACHINE_TOKEN_ENV_VAR = 'TERMINUS_MACHINE_TOKEN';
    const USERNAME_ENV_VAR = 'TERMINUS_USERNAME';
    const VERIFY_CERT_ENV_VAR = 'TERMINUS_VERIFY_HOST_CERT';

    /**
     * Initializes context
     *
     * @param array $parameters Parameters from the Behat YAML config file
     * @return
     */
    public function __construct($parameters)
    {
        date_default_timezone_set('UTC');

        if (!isset($parameters['machine_token'])) {
            if (getenv(self::MACHINE_TOKEN_ENV_VAR) === false) {
                throw new \Exception(
                    'A machine token must be indicated by setting the environment variable '
                    . self::MACHINE_TOKEN_ENV_VAR
                    . ' or by setting the machine_token parameter in the Behat configuration file.'
                );
            }
            $parameters['machine_token'] = getenv(self::MACHINE_TOKEN_ENV_VAR);
        }

        if (!isset($parameters['username'])) {
            if (getenv(self::USERNAME_ENV_VAR) === false) {
                throw new \Exception(
                    'A user email must be indicated by setting the environment variable '
                    . self::USERNAME_ENV_VAR
                    . ' or by setting the username parameter in the Behat configuration file.'
                );
            }
            $parameters['username'] = getenv(self::USERNAME_ENV_VAR);
        }

        if (!isset($parameters['verify_host_vert'])) {
            $should_verify_host_cert = getenv(self::VERIFY_CERT_ENV_VAR);
            $parameters['verify_host_cert'] = ($should_verify_host_cert === false) || $should_verify_host_cert;
        }

        $tests_root            = dirname(dirname(__DIR__));
        $this->fixtures_dir    = $tests_root . '/fixtures/functional';
        $this->cliroot         = dirname($tests_root);
        $this->parameters      = $parameters;
        $this->start_time      = time();
        $this->connection_info = [
            'host' => $parameters['host'],
            'machine_token' => $parameters['machine_token'],
            'verify_host_cert' => $parameters['verify_host_cert'],
        ];

        $this->cache_dir = $parameters['cache_dir'];
        $this->cache_token_dir = $this->cache_dir . "/tokens";
        $this->plugin_dir = $this->fixtures_dir . '/plugins';
        $this->plugin_dir_name = self::DEFAULT_PLUGIN_DIR_NAME;
    }

    /**
     * Ensures a site of the given name exists
     *
     * @Given /^a site named "([^"]*)"$/
     * @Given /^a site named: (.*)$/
     *
     * @param string $site Name of site to ensure exists
     * @return boolean Always true, else errs
     * @throws \Exception
     */
    public function aSiteNamed($site)
    {
        try {
            $this->iRun("terminus site:lookup $site");
        } catch (TerminusException $e) {
            throw new \Exception("Your user does not have a site named $site.");
        }
        return true;
    }

    /**
     * Ensures a site of the given name exists and belongs to given org
     * @Given /^a site named "([^"]*)" belonging to "([^"]*)"$/
     *
     * @param string $site Name of site to ensure exists
     * @param string $org  Name or UUID of organization to ensure ownership
     * @return boolean Always true, else errs
     */
    public function aSiteNamedBelongingTo($site, $org)
    {
        $output = $this->iGetInfoForTheSite($site);
        if (!$this->checkResult($site, $output)) {
            $this->iCreateSiteNamed('Drupal 7', $site, $org);
            $recurse = $this->aSiteNamedBelongingTo($site, $org);
            return $recurse;
        }
        return true;
    }

    /**
     * @BeforeScenario
     * Runs before each scenario
     *
     * @param [ScenarioEvent] $event Feature information from Behat
     * @return
     */
    public function before($event)
    {
        $this->setCassetteName($event);
        $this->plugin_dir_name = self::DEFAULT_PLUGIN_DIR_NAME;
        $this->environment_variables = [];
    }

    /**
     * Select which plugin directory will be used for the
     * rest of the statements in the current scenario.
     * @When /^I am using "([^"]*)" plugins/
     * @param string $dir_name
     */
    public function selectPluginDir($dir_name)
    {
        $this->plugin_dir_name = $dir_name;
    }

    /**
     * @When /^I am prompted to "([^"]*)" on "([^"]*)" at "([^"]*)"$/
     *
     * @param string $prompt To be output before entering any key
     * @param string $site   Site about which prompt is regarding
     * @param string $url    URL to open after prompt
     * @return
     */
    public function iAmPrompted(
        $prompt,
        $site,
        $url = "https://[[dashboard_host]]/"
    ) {
        echo $prompt . PHP_EOL;
        echo 'Then press any key.';
        $site      = $this->replacePlaceholders($site);
        $site_info = $this->iGetInfoForTheSite($site);
        $url       = $this->replacePlaceholders($url, $site_info);
        $this->openInBrowser($url);
        $line = trim(fgets(STDIN));
    }

    /**
     * Logs in user with username and password set in behat.yml
     * And a blank slate cache
     * @Given I am authenticated
     * @When I log in
     */
    public function iAmAuthenticated()
    {
        $this->iLogIn();
    }

    /**
     * Creates a site for the given name
     * @When /^I create a "([^"]*)" site named "([^"]*)"$/
     *
     * @param string $upstream Which upstream to use as new site's source
     * @param string $name     Name of site to create
     * @param string $org      Name or UUID of organization to own the new site
     * @return
     */
    public function iCreateSiteNamed($upstream, $name, $org = false)
    {
        $append_org = '';
        if ($org !== false) {
            $append_org = '--org=' . $org;
        }
        $this->iRun("terminus site:create $name --label=$name --upstream=\"$upstream\" $append_org");
    }

    /**
     * Queries for info for a given site
     * @Given /^I get info for the site "([^"]*)"$/
     *
     * @param string $site Site to get info on
     * @return string Output from command run
     */
    public function iGetInfoForTheSite($site)
    {
        $return = $this->iRun("terminus site:info $site");
        return $return;
    }

    /**
     * Checks which user Terminus is operating as
     * @Given /^I have at least "([^"]*)" site$/
     * @Given /^I have at least "([^"]*)" sites$/
     *
     * @param integer $min The minimum number of sites to have
     * @return boolean $has_the_min
     */
    public function iHaveAtLeastSite($min)
    {
        $sites       = json_decode($this->iRun('terminus site:list --format=json'));
        $has_the_min = ($min <= count($sites));
        if (!$has_the_min) {
            throw new \Exception(count($sites) . ' sites found.');
        }
        return $has_the_min;
    }

    /**
     * Removes all machine tokens from the running machine
     * @Given I have no saved machine tokens
     *
     * @return boolean
     */
    public function iHaveNoSavedMachineTokens()
    {
        $this->iRun("rm {$this->cache_token_dir}/*");
        return true;
    }

    /**
     * Ensures at least X machine tokens exist in the tokens directory
     * @Given I have at least :num_tokens saved machine token
     * @Given I have at least :num_tokens saved machine tokens
     *
     * @param integer $num_tokens Number of tokens to ensure exist
     * @return boolean
     */
    public function iHaveAtLeastSavedMachineTokens($num_tokens)
    {
        switch ($num_tokens) {
            case 0:
                break;
            case 1:
                $this->iLogIn();
                break;
            default:
                $this->iLogIn();
                $original_name = '[[username]]';
                $original_file = "{$this->cache_token_dir}/$original_name";
                for ($i = 1; $i <= $num_tokens; $i++) {
                    $altered_name = $original_name . $i;
                    $altered_file = "{$this->cache_token_dir}/$altered_name";

                    $this->iRun("cp $original_file $altered_file");
                    $this->iRun("sed -i '.bak' 's/$original_name/$altered_name/' $altered_file");
                }
                break;
        }
        return true;
    }

    /**
     * Ensures at least X machine tokens exist in the tokens directory
     * @Given I have exactly :num_tokens saved machine token
     * @Given I have exactly :num_tokens saved machine tokens
     *
     * @param integer $num_tokens Number of tokens to ensure exist
     * @return boolean
     */
    public function iHaveExactlySavedMachineTokens($num_tokens)
    {
        $this->iHaveNoSavedMachineTokens();
        return $this->iHaveAtLeastSavedMachineTokens($num_tokens);
    }

    /**
     * Checks which user Terminus is operating as
     * @Given /^I have "([^"]*)" site$/
     * @Given /^I have "([^"]*)" sites$/
     * @Given /^I have no sites$/
     *
     * @param integer $num The number of sites to have
     * @return boolean $has_amount
     */
    public function iHaveSites($num = 0)
    {
        $sites      = json_decode($this->iRun('terminus site:list --format=json'));
        $has_amount = ($num === count($sites));
        if (!$has_amount) {
            throw new \Exception(count($sites) . ' sites found.');
        }
        return $has_amount;
    }

    /**
     * Installs given module to given Drupal site
     * @When /^I install the module "([^"]*)" to "([^"]*)"$/
     *
     * @param string $module Name of Drupal module to install
     * @param string $site   Name of the site to which to install
     * @return
     */
    public function iInstallTheModuleTo($module, $site)
    {
        $this->iRun("terminus drush --command='dl $module -y' --site=$site --env=dev");
    }

    /**
     * Logs in user
     * @When /^I log in via machine token "([^"]*)"$/
     * @When /^I log in via machine token$/
     * @When /^I log in$/
     *
     * @param string $token A Pantheon machine token
     * @return
     */
    public function iLogIn($token = '[[machine_token]]')
    {
        $this->iRun("terminus auth:login --machine-token=$token");
    }

    /**
     * Logs in a user with a locally saved machine token
     * @When /^I log in as "([^"]*)"$/
     *
     * @param string $email An email address
     * @return
     */
    public function iLogInAs($email = '[[username]]')
    {
        $this->iRun("terminus auth:login --email=$email");
    }

    /**
     * Logs user out
     * @When I log out
     * @Given I am not authenticated
     *
     * @return
     */
    public function iLogOut()
    {
        $this->iRun("terminus auth:logout");
        $this->iShouldBeLoggedOut();
    }

    /**
     * @When /^I run "([^"]*)"$/
     * @When /^I run: (.*)$/
     * @When /^I run:$/
     * Runs command and saves output
     *
     * @param string $command To be entered as CL stdin
     * @return string Returns output of command run
     */
    public function iRun($command)
    {
        $regex        = '/(?<!\.)terminus/';
        $command = preg_replace($regex, sprintf('bin/terminus', $this->cliroot), $command);
        $command = $this->replacePlaceholders($command);

        // Direct Terminus to run the suite commands on the specified host
        if (isset($this->connection_info['host'])) {
            $command = "TERMINUS_HOST={$this->connection_info['host']} $command";
        }

        // Instruct Terminus whether it is appropriate to verify host cert
        if (isset($this->connection_info['verify_host_cert'])) {
            $verify = $this->connection_info['verify_host_cert'] ? '1' : '0';
            $command = "TERMINUS_VERIFY_HOST_CERT=$verify $command";
        }

        // If there is a VCR mode, include it.
        if (isset($this->parameters['vcr_mode'])) {
            $command = "TERMINUS_VCR_MODE={$this->parameters['vcr_mode']} $command";
            // If there is a fixture indicated by the test, give its information to Terminus.
            if (isset($this->cassette_name)) {
                $command = "TERMINUS_VCR_CASSETTE={$this->cassette_name} $command";
            }
        }

        // Determine which plugin dir we should use
        $plugins = $this->plugin_dir . DIRECTORY_SEPARATOR . $this->plugin_dir_name;
        // Pass the cache directory to the command so that tests don't poison the user's cache.
        $command = "TERMINUS_TEST_MODE=1 TERMINUS_CACHE_DIR=$this->cache_dir TERMINUS_TOKENS_DIR=$this->cache_token_dir TERMINUS_PLUGINS_DIR=$plugins $command";

        // Insert any environment variables defined for this scenario
        foreach ($this->environment_variables as $var => $value) {
            $var = $this->replacePlaceholders($var);
            $value = $this->replacePlaceholders($value);
            $command = "{$var}={$value} $command";
        }

        // Execute the command
        //var_dump($command);
        ob_start();
        passthru($command . ' 2>&1');
        $this->output = ob_get_clean();
        //var_dump($this->output);

        // While Terminus commands might complete their tasks even with PHP warnings
        // or notices, those should still trigger test failures.
        if ($this->checkResult("PHP Warning:", $this->output)) {
            throw new \Exception("The Terminus command generated a PHP Warning:\n{$this->output}\n");
        }
        if ($this->checkResult("PHP Notice:", $this->output)) {
            throw new \Exception("The Terminus command generated a PHP Notice:\n{$this->output}\n");
        }

        return $this->output;
    }

    /**
     * Checks login information
     *
     * @return null|array
     */
    protected function iGetMyLoginInformation()
    {
        return json_decode($this->iRun("terminus auth:whoami --format=json"));
    }

    /**
     * Checks whether the user is logged in or not
     * @Then I should be logged in
     *
     * @throws \Exception If the user is not logged in
     */
    public function iShouldBeLoggedIn()
    {
        if ($this->iGetMyLoginInformation() === null) {
            throw new \Exception('You are logged out.');
        }
    }

    /**
     * Checks whether the user is logged in or not
     * @When /^I should be logged in as (.*)$/
     *
     * @param string $email Email address of the user you should be logged in as
     * @throws \Exception If the user is not logged in, has no email address, or is not logged in with the desired account
     */
    public function iShouldBeLoggedInAs($email)
    {
        $login_info = $this->iGetMyLoginInformation();
        if ($login_info === null) {
            throw new \Exception('You are logged out.');
        }
        if (!isset($login_info['email'])) {
            throw new \Exception('The logged in user does not have an email address.');
        }
        if ($login_info['email'] !== $email) {
            throw new \Exception("The logged in user is {$login_info['email']}, not $email.");
        }
    }

    /**
     * Checks whether the user is logged out
     * @Then I should not be logged in
     * @Then I should be logged out
     *
     * @throws \Exception If the Terminus user is not logged out
     */
    public function iShouldBeLoggedOut()
    {
        if ($this->iGetMyLoginInformation() !== null) {
            throw new \Exception('You are logged in.');
        }
    }

    /**
     * @Then /^I should get:$/
     * @Then /^I should get "([^"]*)"$/
     * @Then /^I should get: "([^"]*)"$/
     * Checks the output for the given string
     *
     * @param string $string Content which ought not be in the output
     * @return boolean $i_have_this True if $string exists in output
     * @throws Exception
     */
    public function iShouldGet($string)
    {
        $i_have_this = $this->iShouldGetOneOfTheFollowing($string);
        return $i_have_this;
    }

    /**
     * Checks the output for the given string that it is of the given log level and with the given string
     *
     * @param string $level The log level expected in output
     * @param string $string Content which ought not be in the output
     * @return boolean If the string was found in the given level of log
     * @throws \Exception
     */
    public function iShouldGetTheLog($level, $string)
    {
        return $this->iShouldGet("[$level] $string");
    }

    /**
     * @Then /^I should get the error:$/
     * @Then /^I should get the error "([^"]*)"$/
     * @Then /^I should get the error: "([^"]*)"$/
     * Checks the output for the given string that it is a error with the given string
     *
     * @param string $string Content which ought not be in the output
     * @return boolean True if $string exists in error output
     * @throws \Exception
     */
    public function iShouldGetTheError($string)
    {
        return $this->iShouldGetTheLog('error', " $string");
    }

    /**
     * @Then /^I should get the notice:$/
     * @Then /^I should get the notice "([^"]*)"$/
     * @Then /^I should get the notice: "([^"]*)"$/
     * Checks the output for the given string that it is a notice with the given string
     *
     * @param string $string Content which ought not be in the output
     * @return boolean True if $string exists in notice output
     * @throws \Exception
     */
    public function iShouldGetTheNotice($string)
    {
        return $this->iShouldGetTheLog('notice', $string);
    }

    /**
     * @Then /^I should get the warning:$/
     * @Then /^I should get the warning "([^"]*)"$/
     * @Then /^I should get the warning: "([^"]*)"$/
     * Checks the output for the given string that it is a warning with the given string
     *
     * @param string $string Content which ought not be in the output
     * @return boolean True if $string exists in warning output
     * @throws \Exception
     */
    public function iShouldGetTheWarning($string)
    {
        return $this->iShouldGetTheLog('warning', $string);
    }

    /**
     * Checks the output for a table with the given headers
     *
     * @Then /^I should see a table with the headers$/
     * @Then /^I should see a table with the headers: ([^"]*)$/
     * @Then /^I should see a table with the headers: "([^"]*)"$/
     *
     * @param string $headers Comma separated row values to match
     * @return boolean true if $headers exists in output
     *
     * @throws \Exception
     */
    public function shouldSeeATableWithHeaders($headers)
    {
        $table_headers = explode(',', $headers);
        foreach ($table_headers as $column) {
            if (!$this->checkResult(trim((string)$column), $this->output)) {
                throw new \Exception("Expected table headers to include: '{$column}' in table:\n{$this->output}\n");
            }
        }

        return true;
    }

    /**
     * Checks the output for a table with the given row values
     *
     * @Then /^I should see a table with rows like:$/
     * @Then /^I should see a table with rows like"([^"]*)"$/
     * @Then /^I should see a table with rows like: "([^"]*)"$/
     *
     * @param $rows string newline separated row values to match
     * @return boolean true if all of the rows are present in the output
     * @throws \Exception
     */
    public function iShouldSeeATableWithRows($rows)
    {
        $lines = explode("\n", $rows);
        foreach ($lines as $line) {
            if (!$this->checkResult(trim((string)$line), $this->output)) {
                throw new \Exception("Expected the row '{$line}' in table:\n{$this->output}\n");
            }
        }

        return true;
    }

    /**
     * Checks the output for a table with the given number of rows
     *
     * @Then I should see a table with :num_rows row
     * @Then I should see a table with :num_rows rows
     * @Then that table should have :num_rows row
     * @Then that table should have :num_rows rows
     *
     * @param integer $num Number of rows to be found in the table
     * @return boolean true if all of the given number of rows are present
     * @throws \Exception
     */
    public function iShouldSeeATableWithSoManyRows($num)
    {
        $lines = explode("\n", $this->output);
        $boundaries = [];
        foreach ($lines as $key => $line) {
            if (strpos(trim($line), '---') === 0) {
                $boundaries[] = $key;
            }
        }
        $row_count = (count($boundaries) < 3) ? 0 : ($boundaries[2] - $boundaries[1] - 1);

        $num_rows = ($num === 'no') ? 0 : (integer)$num;
        if ($num_rows !== $row_count) {
            throw new \Exception("The table had $row_count rows, not $num_rows.");
        }
        return true;
    }

    /**
     * Checks the output for a type of message. Message to match is optional.
     *
     * @Then /^I should see a[n]? (notice|warning|error) message$/
     * @Then /^I should see a[n]? (notice|warning|error) message: (.*)$/
     *
     * @param $type string One of the standard logging levels
     * @param $message string Optional message to match in the output
     * @return bool True if message is the correct type and exists in output if given
     * @throws \Exception
     */
    public function iShouldSeeATypeOfMessage($type, $message = null)
    {
        $expected_message = "[$type]";
        if (!empty($message)) {
            $expected_message .= " {$message}";
        }

        $compressed_output = preg_replace('/\s+/', ' ', $this->output);
        if (strpos($compressed_output, $expected_message) === false) {
            throw new \Exception("Expected $expected_message in message: $this->output");
        }

        return true;
    }

    /**
     * @Then /^I should get a valid UUID/
     * Checks the output for a valid UUID
     *
     * @return bool
     * @throws Exception
     */
    public function iShouldGetValidUuid()
    {
        preg_match(
            '/^([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})$/',
            $this->output,
            $matches
        );
        if (empty($matches)
        && ($this->output != '11111111-1111-1111-1111-111111111111')
        ) {
            throw new \Exception($this->output . ' is not a valid UUID.');
        }
        return true;
    }

    /**
     * @Then /^I should get one of the following:$/
     * @Then /^I should get one of the following "([^"]*)"$/
     * @Then /^I should get one of the following: "([^"]*)"$/
     * Checks the output for the given substrings, comma-separated
     *
     * @param array $list_string Content which ought to be in the output
     * @return boolean True if a $string exists in output
     * @throws Exception
      */
    public function iShouldGetOneOfTheFollowing($list_string)
    {
        $strings  = explode(',', $list_string);
        foreach ($strings as $string) {
            if ($this->checkResult(trim((string)$string), $this->output)) {
                return true;
            }
        }
        throw new \Exception("Actual output:\n" . $this->output);
    }

    /**
     * @Then /^I should not get one of the following:$/
     * @Then /^I should not get one of the following "([^"]*)"$/
     * @Then /^I should not get one of the following: "([^"]*)"$/
     * Checks the output for the given substrings, comma-separated
     *
     * @param array $list_string Content which ought not be in the output
     * @return boolean True if a $string does not exist in output
      */
    public function iShouldNotGetOneOfTheFollowing($list_string)
    {
        try {
            $this->iShouldGetOneOfTheFollowing($list_string);
        } catch (\Exception $e) {
            return true;
        }
        throw new \Exception("Actual output:\n" . $this->output);
    }

    /**
     * Checks for backups made since the test started running
     * @Then /^I should have a new backup$/
     *
     * @return boolean True if new backup exists
     */
    public function iShouldHaveNewBackup()
    {
        $regex = "/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/";
        preg_match_all($regex, $this->output, $matches);
        foreach ($matches[0] as $date) {
            if ($this->start_time < strtotime($date)) {
                return true;
            }
        }
        throw new \Exception('No new backups were created.' . PHP_EOL);
    }

    /**
     * Checks the number of records returned against a given quantity
     * @Then /^I should have "([^"]*)" records$/
     *
     * @param integer $number Number of records to check for
     * @return
     */
    public function iShouldHaveRecords($number)
    {
        $record_count = count((array)json_decode($this->output));
        if ((integer)$number != $record_count) {
            throw new \Exception("Wanted $number records, got " . $record_count . '.');
        }
        return true;
    }

    /**
     * Ensures that you do not recieve param $string as result
     * @Then /^I should not get:$/
     * @Then /^I should not get: "([^"]*)"$/
     * @Then I should not get :string
     *
     * @param string $string Content which ought not be in the output
     * @return boolean True if $string does not exist in output
     */
    public function iShouldNotGet($string)
    {
        if ($this->checkResult((string)$string, $this->output)) {
            throw new \Exception("Actual output:\n" . $this->output);
        }
        return true;
    }

    /**
     * Checks the output against a a type of message.
     *
     * @Then /^I should not see a (notice|warning)$/
     * @Then /^I should not see an (error)$/
     *
     * @param $type string One of the standard logging levels
     * @return bool True if message is the expected type in output is not given
     * @throws \Exception
     */
    public function iShouldNotSeeATypeOfMessage($type, $message = null)
    {
        try {
            $this->iShouldSeeATypeOfMessage($type, $message);
        } catch (\Exception $e) {
            $exception_message = $e->getMessage();
            if ((strpos($exception_message, $type) !== false)) {
                return true;
            }
            throw $e;
        }
        throw new \Exception("Expected no $type in message: $this->output");
    }

    /**
     * Ensures that a user is not on a site's team
     * @Given /^"([^"]*)" is a member of the team on "([^"]*)"$/
     *
     * @param string $member Email address of the member on the team of
     * @param string $site   Site which the member should be on the team of
     * @return boolean True if $member does exists in output
     */
    public function isMemberOfTheTeamOn($member, $site)
    {
        $this->iRun("terminus site:team:list $site");
        $is_member = $this->iShouldGet($member);
        return $is_member;
    }

    /**
     * Ensures that a user is not on a site's team
     * @Given /^"([^"]*)" is not a member of the team on "([^"]*)"$/
     *
     * @param string $member Email address of the member not on the team
     * @param string $site   Site which the member should not be on the team of
     * @return boolean True if $member does not exist in output
     */
    public function isNotMemberOfTheTeamOn($member, $site)
    {
        $this->iRun("terminus site:team:list $site");
        $is_not_member = $this->iShouldNotGet($member);
        return $is_not_member;
    }

    /**
     * Ensures there is no site with the given name. Loops until this is so
     * @Given /^no site named "([^"]*)"$/
     *
     * @param string $site Name of site to ensure does not exist
     * @return boolean Always returns true
     */
    public function noSiteNamed($site)
    {
        try {
            $this->aSiteNamed($site);
        } catch (\Exception $e) {
            return true;
        }
        throw new \Exception("A site named $site was found.");
    }

    /**
     * Gets or sets service level
     * @When /^I set the service level of "([^"]*)" to "([^"]*)"$/
     * @Given /^I check the service level of "([^"]*)"$/
     * @Given /^the service level of "([^"]*)" is "([^"]*)"$/
     *
     * @param string $site          Name of site to work on
     * @param string $service_level If not false, will set service level to this
     * @return
     */
    public function serviceLevel($site, $service_level = null)
    {
        if (is_null($service_level)) {
            $command = "terminus site:info $site --field=service_level";
        } else {
            $command = "terminus service-level:set $service_level --site=$site";
        }
        $this->iRun($command);
    }

    /**
     * Automatically assigns pass/fail/skip to the test result
     * @Then /^I "([^"]*)" the test$/
     *
     * @param string $status Status to assign to the test
     * @return boolean Always true, else errs
     */
    public function setTestStatus($status)
    {
        if ($status == 'pending') {
            throw new \Exception("Implementation of this functionality is pending.");
        }
        throw new \Exception("Test explicitly set to $status");
    }

    /**
     * Checks the the haystack for the needle
     *
     * @param string $needle   That which is searched for
     * @param string $haystack That which is searched inside
     * @return boolean $result True if $nededle was found in $haystack
     */
    private function checkResult($needle, $haystack)
    {
        return strstr($haystack, $this->replacePlaceholders($needle)) !== false;
    }

    /**
     * Returns tags in easy-to-use array format.
     *
     * @param [ScenarioEvent] $event Feature information from Behat
     * @return $tags array An array of strings corresponding to tags
     */
    private function getTags($event)
    {
        $unformatted_tags = $event->getScenario()->getTags();
        $tags = array();

        foreach ($unformatted_tags as $tag) {
            $tag_elements = explode(' ', $tag);
            $index = array_shift($tag_elements);
            if (count($tag_elements) == 1) {
                $tag_elements = array_shift($tag_elements);
            }
            $tags[$index] = $tag_elements;
        }

        return $tags;
    }

    /**
     * Opens param $url in the default browser
     *
     * @param string $url URL to open in browser
     * @return
     */
    private function openInBrowser($url)
    {
        $url = $this->replacePlaceholders($url);
        switch (php_uname('s')) {
            case "Darwin":
                $cmd = "open";
                break;
            case "Windows NT":
                $cmd = "start";
                break;
            case "Linux":
            default:
                $cmd = "xdg-open";
                break;
        }
        exec("$cmd $url");
    }

    /**
     * @When /^This step is implemented I will test: (.*)$/
     * @When /^this step is implemented I will test: (.*)$/
     *
     * @param string $description feature description of what is still pending
     */
    public function thisStepIsPending($description)
    {
        throw new PendingException("Testing $description is pending");
    }

    /**
     * @When /^I enter: (.*)$/
     */
    public function iEnterInput()
    {
        throw new PendingException("Interactivity is not yet implemented");
    }

    /**
     * Exchanges values in given string with square brackets for values
     * in $this->parameters
     *
     * @param string $string       The string to perform replacements on
     * @param array  $replacements Used to replace with non-parameters
     * @return string $string The modified param string
     */
    protected function replacePlaceholders($string, $replacements = array())
    {
        $regex = '~\[\[(.*?)\]\]~';
        preg_match_all($regex, $string, $matches);
        if (empty($replacements)) {
            $replacements = $this->parameters;
        }

        foreach ($matches[1] as $id => $replacement_key) {
            if (isset($replacements[$replacement_key])) {
                $replacement = $replacements[$replacement_key];
                if (is_array($replacement)) {
                    $replacement = array_shift($replacement);
                }
                $string = str_replace($matches[0][$id], $replacement, $string);
            }
        }

        return $string;
    }

    /**
     * Sets $this->cassette_name and returns name of the cassette to be used.
     *
     * @param array $event Feature information from Behat
     * @return string Of scneario name, lowercase, with underscores and suffix
     */
    private function setCassetteName($event)
    {
        $tags = $this->getTags($event);
        $this->cassette_name = false;
        if (isset($tags['vcr'])) {
            $this->cassette_name = $tags['vcr'];
        }
        return $this->cassette_name;
    }

    /**
     * Queries for info for a given site
     *
     * @param string $site Site to get info on
     * @return array Output from command run
     */
    protected function getSiteInfo($site)
    {
        return json_decode($this->iRun("terminus site:info $site --format=json"));
    }

    /**
     * @Then I should see a progress bar with the message: :message
     */
    public function iShouldSeeAProgressBarWithTheMessage($message)
    {
        mb_substr_count(
            $this->output,
            $message
        ) == 1
        &&
        mb_substr_count(
            $this->output,
            'Progress: ░░░░░░░░░░░░░░░░░░░░░░░░░░░░   0%'
        ) == 1
        &&
        mb_substr_count(
            $this->output,
            'Progress: ▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓ 100%'
        ) == 1;
    }
}
