<?php

namespace Tests\Feature\System;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxiesTest extends TestCase
{
    private const PROXY_IP = '10.0.0.5';

    private const CLIENT_IP = '203.0.113.7';

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/_trusted-proxies-probe', fn (Request $request): string => (string) $request->ip());
    }

    public function test_the_trusted_proxies_config_reads_the_env_value(): void
    {
        $_SERVER['TRUSTED_PROXIES'] = '*';

        try {
            $config = require config_path('trustedproxy.php');
        } finally {
            unset($_SERVER['TRUSTED_PROXIES']);
        }

        $this->assertSame('*', $config['proxies']);
    }

    public function test_the_forwarded_header_is_ignored_by_default(): void
    {
        $this->assertNull(config('trustedproxy.proxies'));

        $this->withServerVariables(['REMOTE_ADDR' => self::PROXY_IP])
            ->withHeader('X-Forwarded-For', self::CLIENT_IP)
            ->get('/_trusted-proxies-probe')
            ->assertOk()
            ->assertSeeText(self::PROXY_IP);
    }

    public function test_the_forwarded_client_ip_is_used_when_proxies_are_trusted(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $this->withServerVariables(['REMOTE_ADDR' => self::PROXY_IP])
            ->withHeader('X-Forwarded-For', self::CLIENT_IP)
            ->get('/_trusted-proxies-probe')
            ->assertOk()
            ->assertSeeText(self::CLIENT_IP);
    }

    public function test_a_client_cannot_spoof_its_ip_through_the_trusted_proxy(): void
    {
        config(['trustedproxy.proxies' => '*']);

        $this->withServerVariables(['REMOTE_ADDR' => self::PROXY_IP])
            ->withHeader('X-Forwarded-For', '198.51.100.1, '.self::CLIENT_IP)
            ->get('/_trusted-proxies-probe')
            ->assertOk()
            ->assertSeeText(self::CLIENT_IP)
            ->assertDontSeeText('198.51.100.1');
    }
}
