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
