<?php


namespace Pantheon\Terminus\FunctionalTests;

use PHPUnit\Framework\TestCase;

abstract class FunctionalTestBase extends TestCase {

    /**
     * If there is a terminus token, then log in.
     */
    public function setUp(): void
    {
        $token = getenv('TERMINUS_TOKEN');
        if ($token) {
            $result = static::call_terminus("auth:login --machine-token=$token");
            if ($result->isError()) {
                throw new \Exception("Cannot log in to do test...");
            }
        }
    }

    /**
     * Run a terminus command.
     *
     * @param string $command The command to run
     * @param integer $expected_status The required status code for the provided
     * command.
     */
    protected function terminus($command, $expected_status = 0) : TerminusCommandResult
    {
        $result = static::call_terminus($command);
        $this->assertEquals($expected_status, $result->getStatus(), $result->__toString());
        return $result;
    }

    /**
     * Call terminus phar with command and return output.
     *
     * @param string $command The command to run
     */
    protected static function call_terminus($command) : TerminusCommandResult
    {
        $project_dir = dirname(dirname(__DIR__));
        exec("$project_dir/terminus.phar " . $command, $output, $status);
        return new TerminusCommandResult($output, $status);
    }
}
