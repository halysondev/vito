<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class ViteConfigTest extends TestCase
{
    public function test_it_uses_the_laravel_env_file_for_the_hmr_host(): void
    {
        $expectedUrl = $this->envValue('APP_URL');

        $this->assertNotNull($expectedUrl);

        $expectedHost = parse_url($expectedUrl, PHP_URL_HOST);

        $this->assertIsString($expectedHost);

        $process = new Process([
            'node',
            '--input-type=module',
            '-e',
            <<<'JS'
import { loadConfigFromFile } from 'vite';

const result = await loadConfigFromFile(
    { command: 'serve', mode: 'development' },
    './vite.config.ts',
);

console.log(JSON.stringify({
    host: result?.config.server?.hmr?.host ?? null,
    port: result?.config.server?.port ?? null,
}));
JS,
        ], $this->projectRoot(), [
            'APP_URL' => false,
            'VITE_PORT' => false,
        ]);

        $process->mustRun();

        /** @var array{host: ?string, port: int} $config */
        $config = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($expectedHost, $config['host']);
        $this->assertSame($this->envPort('VITE_PORT', 5173), $config['port']);
    }

    private function envPort(string $key, int $default): int
    {
        $value = $this->envValue($key);

        return $value !== null ? (int) $value : $default;
    }

    private function envValue(string $key): ?string
    {
        $contents = file_get_contents($this->projectRoot().'/.env');

        $this->assertNotFalse($contents);

        if (! preg_match('/^'.preg_quote($key, '/').'=(.*)$/m', $contents, $matches)) {
            return null;
        }

        return trim($matches[1], " \t\n\r\0\x0B\"'");
    }

    private function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }
}
