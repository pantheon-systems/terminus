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
    "user_uuid": "dca4f8cd-9ec2-4117-957f-fc5230c23737",
    "session": "dca4f8cd-9ec2-4117-957f-fc5230c23737:20e4ceb0-b224-11e4-94f5-bc764e111d20:jakuWJ8hw4PGMq9Plm9wk",
    "session_expire_time": 1739299351,
    "email": "mike+test@mikevanwinkle.com"
}'));

/**
 * Modified match function to replace VCR\RequestMatcher::matchHeaders() Returns true if the headers of both specified requests match.
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
