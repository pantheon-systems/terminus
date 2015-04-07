<?php
/**
 * Bootstrap file for unit tests
 */
define('CLI_ROOT', dirname(__DIR__) );
define('TERMINUS_CMD','php '.CLI_ROOT.'/php/boot-fs.php');
putenv('CLI_TEST_MODE=1');
require_once CLI_ROOT.'/php/boot-fs.php';
Terminus::set_config('nocache',TRUE);
Terminus::set_config('debug',false);
use Terminus\Fixtures;
use Terminus\Session;

// Set some dummy credentials
Session::setData(json_decode('{
    "user_uuid": "0ffec038-4410-43d0-a404-46997f672d7a",
    "session": "0ffec038-4410-43d0-a404-46997f672d7a%3A6dc10b96-dd7a-11e4-8758-bc764e1113b5%3ASnT0EvWm3hAFLPP6YLBgr",
    "session_expire_time": 1739299351,
    "email": "bensheldon+pantheontest@gmail.com"
}'));

/**
 * Modified match function to replace VCR\RequestMatcher::matchHeaders()
 * Returns true if the headers of both specified requests match.
 *
 * @param  Request $first  First request to match.
 * @param  Request $second Second request to match.
 *
 * @return boolean True if the headers of both specified requests match.
 */
\VCR\VCR::configure()->addRequestMatcher('headers','custom_terminus_match_headers');
function custom_terminus_match_headers($first, $second) {
    $firstHeaders = $first->getHeaders();
    foreach ($second->getHeaders() as $key => $pattern) {
        //normalize the cookie to ensure match
        if ('Cookie' == $key) return true;
        if ($pattern !== $firstHeaders[$key]) {
              var_dump($pattern);
            return false;
        }
    }

    return true;
}
