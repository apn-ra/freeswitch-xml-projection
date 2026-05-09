<?php

declare(strict_types=1);

namespace APNTalk\FreeSwitchXmlProjection\Tests\Live;

use PHPUnit\Framework\TestCase;

final class FreeSwitchXmlCurlLiveSmokeTest extends TestCase
{
    public function testLiveDockerFreeSwitchXmlCurlSipAuthFlow(): void
    {
        if (getenv('FREESWITCH_XML_PROJECTION_LIVE_SMOKE') !== '1') {
            self::markTestSkipped('Set FREESWITCH_XML_PROJECTION_LIVE_SMOKE=1 to run the live Docker FreeSWITCH smoke test.');
        }

        $root = dirname(__DIR__, 2);
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/tools/live-smoke/run-live-smoke.php');
        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptorSpec, $pipes, $root);

        self::assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        self::assertSame(0, $exitCode, trim($stdout . "\n" . $stderr));
        self::assertStringContainsString('[live-smoke] passed.', $stdout);
    }
}
