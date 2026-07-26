<?php

declare(strict_types=1);

namespace Cable8mm\NanoAI\Tests\Support;

/**
 * Local mock server that starts once per test file and is shared by multiple tests.
 * (Since Pest's beforeAll/afterAll run in a static context, state is stored in
 * this class's static properties rather than instances.)
 */
final class MockServer
{
    /** @var resource|null */
    private static $process = null;

    private static ?string $baseUrl = null;

    public static function start(int $port = 8973): string
    {
        if (self::$process !== null) {
            return self::$baseUrl;
        }

        $router = __DIR__.'/router.php';
        $command = sprintf('php -S 127.0.0.1:%d %s', $port, escapeshellarg($router));

        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );

        if (! is_resource($process)) {
            throw new \RuntimeException('Failed to start the test mock server.');
        }

        self::$process = $process;
        self::$baseUrl = "http://127.0.0.1:{$port}";
        self::waitUntilReady();

        return self::$baseUrl;
    }

    public static function baseUrl(): string
    {
        if (self::$baseUrl === null) {
            throw new \RuntimeException('MockServer::start() must be called first.');
        }

        return self::$baseUrl;
    }

    public static function stop(): void
    {
        if (self::$process !== null && is_resource(self::$process)) {
            proc_terminate(self::$process);
            proc_close(self::$process);
        }

        self::$process = null;
        self::$baseUrl = null;
    }

    private static function waitUntilReady(int $maxAttempts = 50): void
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $ch = curl_init(self::$baseUrl.'/success');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 200);
            curl_exec($ch);
            $errno = curl_errno($ch);
            curl_close($ch);

            if ($errno === 0) {
                return;
            }

            usleep(50_000);
        }

        self::stop();
        throw new \RuntimeException('Mock server did not become ready within the time limit.');
    }
}
