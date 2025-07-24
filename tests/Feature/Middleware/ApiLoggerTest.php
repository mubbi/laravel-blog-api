<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiLoggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Ensure logging is enabled for tests
        Config::set('api-logger.enabled', true);
        // Create a test route using the middleware
        Route::middleware('api.logger')->post('/test-api-logger', function () {
            return response()->json(['success' => true, 'token' => 'shouldbemasked']);
        });
    }

    public function test_logs_request_and_response_with_masking()
    {
        Log::spy();
        $payload = [
            'username' => 'testuser',
            'password' => 'supersecret',
        ];
        $headers = [
            'Authorization' => 'Bearer sometoken',
            'X-Request-Id' => 'test-request-id-123',
        ];

        $response = $this->postJson('/test-api-logger', $payload, $headers);
        $response->assertOk();
        $response->assertJson(['success' => true]);

        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) use ($headers) {
            return $message === 'API Request'
                && $context['request_id'] === $headers['X-Request-Id']
                && $context['body']['password'] === '***MASKED***'
                && $context['headers']['authorization'] === '***MASKED***'
                && $context['response_body']['token'] === '***MASKED***';
        })->once();
    }

    public function test_does_not_log_when_disabled()
    {
        Config::set('api-logger.enabled', false);
        Log::spy();
        $response = $this->postJson('/test-api-logger', []);
        $response->assertOk();
        Log::shouldNotHaveReceived('info');
    }

    public function test_logs_with_custom_masked_headers_and_body_keys()
    {
        Config::set('api-logger.masked_headers', ['authorization', 'x-custom-header']);
        Config::set('api-logger.masked_body_keys', ['password', 'secret']);
        Log::spy();
        $payload = [
            'username' => 'testuser',
            'password' => 'supersecret',
            'secret' => 'topsecret',
        ];
        $headers = [
            'Authorization' => 'Bearer sometoken',
            'X-Custom-Header' => 'customvalue',
            'X-Request-Id' => 'custom-id-456',
        ];
        $response = $this->postJson('/test-api-logger', $payload, $headers);
        $response->assertOk();
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return $context['headers']['authorization'] === '***MASKED***'
                && $context['headers']['x-custom-header'] === '***MASKED***'
                && $context['body']['password'] === '***MASKED***'
                && $context['body']['secret'] === '***MASKED***';
        })->once();
    }

    public function test_logs_all_context_fields()
    {
        Log::spy();
        $payload = ['username' => 'testuser'];
        $headers = [
            'X-Request-Id' => 'all-fields-id',
        ];
        $response = $this->postJson('/test-api-logger', $payload, $headers);
        $response->assertOk();
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) use ($headers) {
            return $context['request_id'] === $headers['X-Request-Id']
                && isset($context['ip'])
                && $context['method'] === 'POST'
                && str_contains($context['uri'], '/test-api-logger')
                && $context['response_status'] === 200
                && is_numeric($context['duration_ms']);
        })->once();
    }

    public function test_logs_non_json_response()
    {
        Route::middleware('api.logger')->get('/test-non-json', function () {
            return 'plain text response';
        });
        Log::spy();
        $response = $this->get('/test-non-json');
        $response->assertOk();
        $response->assertSee('plain text response');
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return $context['response_body'] === 'plain text response';
        })->once();
    }

    public function test_logs_with_empty_headers_and_body()
    {
        Log::spy();
        $response = $this->postJson('/test-api-logger', [], []);
        $response->assertOk();
        Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
            return is_array($context['headers']) && is_array($context['body']);
        })->once();
    }
}
