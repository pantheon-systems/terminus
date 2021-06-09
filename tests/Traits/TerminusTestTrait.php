<?php

namespace Pantheon\Terminus\Tests\Traits;

/**
 * Trait TerminusTestTrait
 *
 * @package Pantheon\Terminus\Tests\Traits
 */
trait TerminusTestTrait {

    /**
     * Run a terminus command.
     *
     * @param string $command The command to run
     */
    protected static function call_terminus($command)
    {
        $project_dir = dirname(dirname(__DIR__));
        exec(
            sprintf("%s/%s %s", $project_dir, TERMINUE_BIN_FILE, $command,),
            $output,
            $status
        );
        $output = implode("\n", $output);

        return [$output, $status];
    }

    /**
     * Run a terminus command.
     *
     * @param string $command The command to run
     * @param integer $status The required status code for the
     *   provided command
     */
    protected function terminus($command, $expected_status = 0): ?string
    {
        [$output, $status] = static::call_terminus($command);
        $this->assertEquals($expected_status, $status, $output);
        if (is_array($output)) {
            join("", $output);
        }
        return $output;
    }

    /**
     * @param $command
     * @param int $expected_status
     *
     * @return array|null
     * @throws \JsonException
     */
    protected function terminusJsonResponse($command, $expected_status = 0): array
    {
        $response = $this->terminus(
            $command . " --format=json",
            $expected_status
        );
        return json_decode(
            $response,
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }



}
