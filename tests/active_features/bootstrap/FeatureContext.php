<?php

namespace Pantheon\Terminus\FeatureTests;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Terminus\Exceptions\TerminusException;

/**
 * Features context for Behat feature testing
 */
class FeatureContext implements Context, SnippetAcceptingContext
{
    public $cliroot = '';
    private $cache_file_name;
    private $cache_token_dir;
    private $parameters;
    private $output;
    private $start_time;

    /**
     * Initializes context
     *
     * @param [array] $parameters Parameters from the Behat YAML config file
     * @return [void]
     */
    public function __construct($parameters)
    {
        date_default_timezone_set('UTC');
        $this->cliroot          = dirname(dirname(__DIR__)) . '/..';
        $this->parameters      = $parameters;
        $this->start_time      = time();
        $this->cache_file_name = $_SERVER['HOME'] . '/.terminus/cache/session';
        $this->cache_token_dir = $_SERVER['HOME'] . '/.terminus/cache/tokens';
        $this->connection_info = ['host' => $parameters['host'], 'machine_token' => $parameters['machine_token'],];
    }

    /**
     * Ensures the user has access to the given payment instrument
     * @Given /^a payment instrument with uuid "([^"]*)"$/
     *
     * @param [string] $instrument_uuid UUID of a payment instrument
     * @return [void]
     */
    public function aPaymentInstrumentWithUuid($instrument_uuid)
    {
        $instruments = $this->iRun('terminus upstream:list');
        try {
            $uuid = new PyStringNode($this->replacePlaceholders($instrument_uuid));
            $this->iShouldGet($uuid);
        } catch (\Exception $e) {
            throw new \Exception("Your user does not have access to instrument $instrument_uuid.");
        }
    }

    /**
     * Ensures a site of the given name exists
     * @Given /^a site named "([^"]*)"$/
     *
     * @param [string] $site Name of site to ensure exists
     * @return [boolean] Always true, else errs
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
     * @param [string] $site Name of site to ensure exists
     * @param [string] $org  Name or UUID of organization to ensure ownership
     * @return [boolean] Always true, else errs
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
     * @return [void]
     */
    public function before($event)
    {
        $this->setCassetteName($event);
    }

    /**
     * Changes or displays mode, given or not, of given site
     * @Given /^the connection mode of "([^"]*)" is "([^"]*)"$/
     * @When /^I set the connection mode on "([^"]*)" to "([^"]*)"$/
     * @When /^I check the connection mode on "([^"]*)"$/
     *
     * @param [string] $site Site to change or view connection mode of
     * @param [string] $mode If set, changes mode to given. Else, displays mode
     * @return [void]
     */
    public function connectionMode($site, $mode = null)
    {
        if (is_null($mode)) {
            $command = "terminus site env:info dev --site=$site --field=connection_mode";
        } else {
            $command = "terminus connection:set $mode --site=$site --env=dev";
        }
        $this->iRun($command);
    }

    /**
     * Adds given hostname to given site's given environment
     * @When /^I add hostname "([^"]*)" to the "([^"]*)" environment of "([^"]*)"$/
     *
     * @param [string] $hostname Hostname to add
     * @param [string] $env      Environment on which to add hostname
     * @param [string] $site     Site on which to add hostname
     * @return [void]
     */
    public function iAddHostnameToTheEnvironmentOf($hostname, $env, $site)
    {
        $this->iRun("terminus domain:add $hostname --site=$site --env=$env");
    }

    /**
     * Adds $email user from $site
     * @When /^I add "([^"]*)" to the team on "([^"]*)"$/
     *
     * @param [string] $email Email address of user to add
     * @param [string] $site  Name of the site on which to operate
     * @return [void]
     */
    public function iAddToTheTeamOn($email, $site)
    {
        $this->iRun("terminus site:team:add --site=$site --member=$email");
    }

    /**
     * @When /^I am prompted to "([^"]*)" on "([^"]*)" at "([^"]*)"$/
     *
     * @param [string] $prompt To be output before entering any key
     * @param [string] $site   Site about which prompt is regarding
     * @param [string] $url    URL to open after prompt
     * @return [void]
     */
    public function iAmPrompted(
        $prompt,
        $site,
        $url = "https://[[dashboard_host]]/"
    ) {
        echo $prompt . PHP_EOL;
        echo 'Then press any key.';
        $site      = $this->replacePlaceholders($site);
        $site_info = $this->iGetInfoForTheSite($site, $return_hash = true);
        $url       = $this->replacePlaceholders($url, $site_info);
        $this->openInBrowser($url);
        $line = trim(fgets(STDIN));
    }

    /**
     * Logs in user with username and password set in behat.yml
     * And a blank slate cache
     * @Given /^I am authenticated$/
     *
     * @return [void]
     */
    public function iAmAuthenticated()
    {
        $this->iLogIn();
    }

    /**
     * Attaches a given organization as payee of given site
     * @When /^I attach the instrument "([^"]*)" to site "([^"]*)"$/
     *
     * @param [string] $uuid UUID of organization to attach as payee
     * @param [string] $site Name of site on which to attach
     * @return [void]
     */
    public function iAttachTheInstrument($uuid, $site)
    {
        $this->iRun("terminus payment-method:set $uuid --site=$site");
    }

    /**
     * @Given /^I check the list of environments on "([^"]*)"$/
     *
     * @param [string] $site Site to check environments of
     * @return [string] $environments Environment list
     */
    public function iCheckTheListOfEnvironmentsOn($site)
    {
        $environments = $this->iRun("terminus env:list --site=$site");
        return $environments;
    }

    /**
     * Checks to see if a URL is valid
     * @Then /^I check the URL "([^"]*)" for validity$/
     *
     * @param [string] $url URL to check for validity
     * @return [void]
     */
    public function iCheckTheUrlForValidity($url)
    {
        $url = $this->replacePlaceholders($url);
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \Exception("$url URL is not valid.");
        }
    }

    /**
     * Checks which user Terminus is operating as
     * @Given /^I check the user I am logged in as$/
     *
     * @return [void]
     */
    public function iCheckTheUserAmLoggedInAs()
    {
        $this->iRun('terminus auth:whoami');
    }

    /**
     * Clears site caches
     * @When /^I clear the caches on the "([^"]*)" environment of "([^"]*)"$/
     *
     * @param [string] $env  Environment on which to clear caches
     * @param [string] $site Site on which to clear caches
     * @return [void]
     */
    public function iClearTheCaches($env, $site)
    {
        $this->iRun("terminus env:clear-cache $env --site=$site");
    }

    /**
     * @When /^I clone the "([^"]*)" environment into the "([^"]*)" environment on "([^"]*)"$/
     *
     * @param [string] $from_env Environment to clone from
     * @param [string] $to_env   Environment to clone into
     * @param [string] $site     Site on which to clone an environment
     * @return [void]
     */
    public function iCloneTheEnvironment($from_env, $to_env, $site)
    {
        $this->iRun("terminus env:clone --site=$site --from-env=$from_env --to-env=$to_env --yes");
    }

    /**
     * Commits changes to given site's given env with given message
     * @When /^I commit changes to the "([^"]*)" environment of "([^"]*)" with message "([^"]*)"$/
     *
     * @param [string] $env     Name of environment on which to commit
     * @param [string] $site    Name of site on which to commit
     * @param [string] $message Message for commit
     * @return [void]
     */
    public function iCommitChanges($env, $site, $message)
    {
        $this->iRun("terminus env:commit $env --site=$site --message=" . '"' . $message . '" --yes');
    }

    /**
     * Creates a site for the given name
     * @When /^I create a "([^"]*)" site named "([^"]*)"$/
     *
     * @param [string] $upstream Which upstream to use as new site's source
     * @param [string] $name     Name of site to create
     * @param [string] $org      Name or UUID of organization to own the new site
     * @return [void]
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
     * Creates a multidev env of given name on given site cloning given env
     * @When /^I create multidev environment "([^"]*)" from "([^"]*)" on "([^"]*)"$/
     *
     * @param [string] $multidev Name of new multidev environment
     * @param [string] $env      Name of environment to copy
     * @param [string] $site     Name of site on which to create multidev env
     * @return [void]
     */
    public function iCreateMultidevEnv($multidev, $env, $site)
    {
        $this->iRun("terminus multidev:create --site=$site --to-env=$multidev --from-env=$env");
    }

    /**
     * Deletes a site of the given name
     * @When /^I delete the site named "([^"]*)"$/
     *
     * @param [string] $site Name of site to delete
     * @return [void]
     */
    public function iDeleteTheSiteNamed($site)
    {
        $this->iRun("terminus site:delete $site --yes");
    }

    /**
     * @Given /^I deploy the "([^"]*)" environment from "([^"]*)" of "([^"]*)" with the message "([^"]*)"$/
     *
     * @param [string] $env     Name of environment to deploy
     * @param [string] $from    Name of environment to deploy from
     * @param [string] $site    Name of site on which to deploy environment
     * @param [string] $message Commit message for the log
     * @return [void]
     */
    public function iDeployTheEnvironmentOf($env, $from, $site, $message)
    {
        $this->iRun("terminus env:deploy --site=$site --to-env=$env --from-env=$from --note=$note");
    }

    /**
     * Intentionally expires the user's session
     * @When /^I expire my session$/
     *
     * @return [void]
     */
    public function iExpireMySession()
    {
        $session = json_decode(file_get_contents($this->cache_file_name));
        $session->session_expire_time = -386299860;
        file_put_contents($this->cache_file_name, $session);
    }

    /**
     * Queries for info for a given site
     * @Given /^I get info for the "([^"]*)" environment of "([^"]*)"$/
     *
     * @param [string] $env  Environment to get info on
     * @param [string] $site Site to get info on
     * @return [string] Output from command run
     */
    public function iGetInfoForTheEnvironmentOf($env, $site)
    {
        $return = json_decode($this->iRun("terminus env:info $env --site=$site --env=$env --format=json"));
        return $return;
    }

    /**
     * Queries for info for a given site
     * @Given /^I get info for the site "([^"]*)"$/
     *
     * @param [string] $site Site to get info on
     * @return [string] Output from command run
     */
    public function iGetInfoForTheSite($site)
    {
        $return = json_decode($this->iRun("terminus site:info $site --format=json"));
        return $return;
    }

    /**
     * Checks which user Terminus is operating as
     * @Given /^I have at least "([^"]*)" site$/
     * @Given /^I have at least "([^"]*)" sites$/
     *
     * @param [integer] $min The minimum number of sites to have
     * @return [boolean] $has_the_min
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
     * @Given I have at least :num_tokens saved machine tokens
     *
     * @param integer $num_tokens Number of tokens to ensure exist
     * @return boolean
     */
    public function iHaveSavedMachineTokens($num_tokens)
    {
        switch ($num_tokens) {
            case 0:
                break;
            case 1:
                $this->iLogIn();
                break;
            default:
                $this->iLogIn();
                for ($i = 1; $i <= $num_tokens; $i++) {
                    $this->iRun("cp {$this->cache_token_dir}/[[username]]$i");
                }
                break;
        }
        return true;
    }

    /**
     * Checks which user Terminus is operating as
     * @Given /^I have "([^"]*)" site$/
     * @Given /^I have "([^"]*)" sites$/
     * @Given /^I have no sites$/
     *
     * @param [integer] $num The number of sites to have
     * @return [boolean] $has_amount
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
     * @When /^I initialize the "([^"]*)" environment on "([^"]*)"$/
     *
     * @param [string] $env  Name of environment to initialize
     * @param [string] $site Name of site on which to initialize environment
     * @return [void]
     */
    public function iInitializeTheEnvironmentOn($env, $site)
    {
        $this->iRun("terminus env:deploy $env --site=$site");
    }

    /**
     * Installs given module to given Drupal site
     * @When /^I install the module "([^"]*)" to "([^"]*)"$/
     *
     * @param [string] $module Name of Drupal module to install
     * @param [string] $site   Name of the site to which to install
     * @return [void]
     */
    public function iInstallTheModuleTo($module, $site)
    {
        $this->iRun("terminus drush --command='dl $module -y' --site=$site --env=dev");
    }

    /**
     * Lists all hostnames of the given site's given environment
     * @Given /^I list the hostnames on the "([^"]*)" environment of "([^"]*)"$/
     *
     * @param [string] $env  Environment to list hostnames of
     * @param [string] $site Name of the site to list the hostnames of
     * @return [void]
     */
    public function iListTheHostnamesOn($env, $site)
    {
        $this->iRun("terminus domain:list --site=$site --env=$env");
    }

    /**
     * Checks the
     * @Given /^I check the payment instrument of "([^"]*)"$/
     *
     * @param [string] $site Name of site to check payment instrument of
     * @return [void]
     */
    public function iCheckThePaymentInstrumentOfSite($site)
    {
        $this->iRun("terminus payment-method:info --site=$site");
    }

    /**
     * Lists all sites user is on the team of
     * @When /^I list the sites$/
     *
     * @return [void]
     */
    public function iListTheSites()
    {
        $this->iRun('terminus site:list');
    }

    /**
     * Lists team members
     * @Given /^I list the team members on "([^"]*)"$/
     *
     * @param [string] $site Name of site of which to retrieve team members
     * @return [void]
     */
    public function iListTheTeamMembersOn($site)
    {
        $this->iRun("terminus site:team:list --site=$site");
    }

    /**
     * List the backups of the given environment of the given site
     * @When /^I list the backups of the "([^"]*)" environment of "([^"]*)"$/
     *
     * @param [string] $env  Environment of which to list the backups
     * @param [string] $site Site of which to list the backups
     * @return [string] Output to the CL
     */
    public function iListTheBackupsOf($env, $site)
    {
        $return = $this->iRun("terminus backup:list --site=$site --env=$env");
        return $return;
    }

    /**
     * Logs in user
     * @When /^I log in via machine token "([^"]*)"$/
     * @When /^I log in via machine token$/
     * @When /^I log in$/
     *
     * @param [string] $token A Pantheon machine token
     * @return [void]
     */
    public function iLogIn($token = '[[machine_token]]')
    {
        $this->iRun("terminus auth:login --machine-token=$token");
    }

    /**
     * Logs in a user with a locally saved machine token
     * @When /^I log in as "([^"]*)"$/
     *
     * @param [string] $email An email address
     * @return [void]
     */
    public function iLogInAs($email = '[[username]]')
    {
        $this->iRun("terminus auth:login --email=$email");
    }

    /**
     * Logs user out
     * @When /^I log out$/
     * @Given /^I am not authenticated$/
     *
     * @return [void]
     */
    public function iLogOut()
    {
        $this->iRun("terminus auth:logout");
    }

    /**
     * Makes a backup of given elements of given site's given environment
     * @When /^I back up "([^"]*)" elements of the "([^"]*)" environment of "([^"]*)"/
     * @When /^I back up the "([^"]*)" element of the "([^"]*)" environment of "([^"]*)"/
     *
     * @param [string] $elements Elements to back up
     * @param [string] $env      Environment to back up
     * @param [string] $site     Name of the site to back up
     * @return [void]
     */
    public function iMakeBackupElementsOfTheEnvironment($elements, $env, $site)
    {
        $this->iRun("terminus backup:create --site=$site --env=$env --element=$elements");
    }

    /**
     * @When /^I merge the "([^"]*)" environment into the "([^"]*)" environment on "([^"]*)"$/
     *
     * @param [string] $from_env Environment to merge from
     * @param [string] $to_env   Environment to merge into
     * @param [string] $site     Name of site on which to merge environments
     * @return [void]
     */
    public function iMergeTheEnvironment($from_env, $to_env, $site)
    {
        $this->setTestStatus('pending');
    }

    /**
     * Removes $email user from $site
     * @When /^I remove "([^"]*)" from the team on "([^"]*)"$/
     *
     * @param [string] $email Email address of user to add
     * @param [string] $site  Name of the site on which to operate
     * @return [void]
     */
    public function iRemoveFromTheTeamOn($email, $site)
    {
        $this->iRun("terminus site:team:remove --site=$site --member=$email");
    }

    /**
     * @Given /^I restore the "([^"]*)" environment of "([^"]*)" from backup$/
     *
     * @param [string] $env  Environment to restore from backup
     * @param [string] $site Site to restore from backup
     * @return [void]
     */
    public function iRestoreTheEnvironmentOfFromBackup($env, $site)
    {
        $this->setTestStatus('pending');
    }

    /**
     * @When /^I run "([^"]*)"$/
     * Runs command and saves output
     *
     * @param [string] $command To be entered as CL stdin
     * @return [string] Returns output of command run
     */
    public function iRun($command)
    {
        $regex        = '/(?<!\.)terminus/';
        $terminus_cmd = sprintf('bin/terminus.php', $this->cliroot);

        $command      = $this->replacePlaceholders($command);
        if (isset($this->connection_info['host'])) {
            $command = "TERMINUS_HOST={$this->connection_info['host']} $command -vvv";
        }
        if (isset($this->cassette_name)) {
            $command = "TERMINUS_VCR_CASSETTE={$this->cassette_name} $command";
        }
        if (!empty($mode = $this->parameters['vcr_mode'])) {
            $command = "TERMINUS_VCR_MODE=$mode $command";
        }
        $command = preg_replace($regex, $terminus_cmd, $command);

        ob_start();
        passthru($command . ' 2>&1');
        $this->output = ob_get_clean();

        return $this->output;
    }

    /**
     * @Then /^I should get:$/
     * @Then /^I should get "([^"]*)"$/
     * @Then /^I should get: "([^"]*)"$/
     * Checks the output for the given string
     *
     * @param [string] $string Content which ought not be in the output
     * @return [boolean] $i_have_this True if $string exists in output
     * @throws Exception
     */
    public function iShouldGet($string)
    {
        $i_have_this = $this->iShouldGetOneOfTheFollowing($string);
        return $i_have_this;
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
     * @param [array] $list_string Content which ought to be in the output
     * @return [boolean] True if a $string exists in output
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
     * @param [array] $list_string Content which ought not be in the output
     * @return [boolean] True if a $string does not exist in output
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
     * @return [boolean] True if new backup exists
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
     * @param [integer] $number Number of records to check for
     * @return [void]
     */
    public function iShouldHaveRecords($number)
    {
        preg_match("/.*(\[{.*}\]).*/", str_replace("\n", '', $this->output), $matches);
        $records = json_decode($matches[1]);
        if ((integer)$number != count($records)) {
            throw new \Exception("Wanted $number records, got " . count($records) . '.');
        }
        return true;
    }

    /**
     * Ensures that you do not recieve param $string as result
     * @Then /^I should not get:$/
     * @Then /^I should not get: "([^"]*)"$/
     *
     * @param [string] $string Content which ought not be in the output
     * @return [boolean] True if $string does not exist in output
     */
    public function iShouldNotGet($string)
    {
        if ($this->checkResult((string)$string, $this->output)) {
            throw new \Exception("Actual output:\n" . $this->output);
        }
        return true;
    }

    /**
     * Ensures that a user is not on a site's team
     * @Given /^"([^"]*)" is a member of the team on "([^"]*)"$/
     *
     * @param [string] $member Email address of the member on the team of
     * @param [string] $site   Site which the member should be on the team of
     * @return [boolean] True if $member does exists in output
     */
    public function isMemberOfTheTeamOn($member, $site)
    {
        $this->iRun("terminus site:team:list --site=$site");
        $is_member = $this->iShouldGet($member);
        return $is_member;
    }

    /**
     * Ensures that a user is not on a site's team
     * @Given /^"([^"]*)" is not a member of the team on "([^"]*)"$/
     *
     * @param [string] $member Email address of the member not on the team
     * @param [string] $site   Site which the member should not be on the team of
     * @return [boolean] True if $member does not exist in output
     */
    public function isNotMemberOfTheTeamOn($member, $site)
    {
        $this->iRun("terminus site:team:list --site=$site");
        $is_not_member = $this->iShouldNotGet($member);
        return $is_not_member;
    }

    /**
     * Ensures there is no site with the given name. Loops until this is so
     * @Given /^no site named "([^"]*)"$/
     *
     * @param [string] $site Name of site to ensure does not exist
     * @return [boolean] Always returns true
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
     * @param [string] $site          Name of site to work on
     * @param [string] $service_level If not false, will set service level to this
     * @return [void]
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
     * @param [string] $status Status to assign to the test
     * @return [boolean] Always true, else errs
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
     * @param [string] $needle   That which is searched for
     * @param [string] $haystack That which is searched inside
     * @return [boolean] $result True if $nededle was found in $haystack
     */
    private function checkResult($needle, $haystack)
    {
        $needle = $this->replacePlaceholders($needle);
        $result = preg_match("#" . preg_quote($needle . "#s"), $haystack);
        return $result;
    }

    /**
     * Returns tags in easy-to-use array format.
     *
     * @param [ScenarioEvent] $event Feature information from Behat
     * @return $tags [array] An array of strings corresponding to tags
     */
    private function getTags($event)
    {
        $unformatted_tags = $event->getScenario()->getTags();
        $tags = array();

        foreach ($unformatted_tags as $tag) {
            $tag_elements = explode(' ', $tag);
            $index        = null;
            if (count($tag_elements < 1)) {
                $index = array_shift($tag_elements);
            }
            if (count($tag_elements == 1)) {
                $tag_elements = array_shift($tag_elements);
            }
            $tags[$index] = $tag_elements;
        }

        return $tags;
    }

    /**
     * Opens param $url in the default browser
     *
     * @param [string] $url URL to open in browser
     * @return [void]
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
     * Reads one line from STDIN
     *
     * @return [string] $line
     */
    private function read()
    {
        $line = trim(fgets(STDIN));
        return $line;
    }

    /**
     * Exchanges values in given string with square brackets for values
     * in $this->parameters
     *
     * @param [string] $string       The string to perform replacements on
     * @param [array]  $replacements Used to replace with non-parameters
     * @return [string] $string The modified param string
     */
    private function replacePlaceholders($string, $replacements = array())
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
     * @param [array] $event Feature information from Behat
     * @return [string] Of scneario name, lowercase, with underscores and suffix
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
}
