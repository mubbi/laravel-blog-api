<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Ensure logging is enabled for tests
    Config::set('api-logger.enabled', true);
    // Create a test route using the middleware
    Route::middleware('api.logger')->post('/test-api-logger', function () {
        return response()->json(['success' => true, 'token' => 'shouldbemasked']);
    });
});

test('logs request and response with masking', function () {
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
});

test('does not log when disabled', function () {
    Config::set('api-logger.enabled', false);
    Log::spy();
    $response = $this->postJson('/test-api-logger', []);
    $response->assertOk();
    Log::shouldNotHaveReceived('info');
});

test('logs with custom masked headers and body keys', function () {
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
});

test('logs all context fields', function () {
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
});

test('logs non json response', function () {
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
});

test('logs with empty headers and body', function () {
    Log::spy();
    $response = $this->postJson('/test-api-logger', [], []);
    $response->assertOk();
    Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
        return is_array($context['headers']) && is_array($context['body']);
    })->once();
});

test('masks nested sensitive fields like access_token and refresh_token', function () {
    Route::middleware('api.logger')->post('/test-nested-masking', function () {
        return response()->json([
            'status' => true,
            'data' => [
                'user' => [
                    'id' => 1,
                    'name' => 'Test User',
                    'access_token' => 'secret-access-token-123',
                    'refresh_token' => 'secret-refresh-token-456',
                ],
            ],
        ]);
    });

    Log::spy();
    $payload = [
        'data' => [
            'access_token' => 'request-access-token-789',
            'refresh_token' => 'request-refresh-token-012',
        ],
    ];

    $response = $this->postJson('/test-nested-masking', $payload);
    $response->assertOk();

    Log::shouldHaveReceived('info')->withArgs(function ($message, $context) {
        return $message === 'API Request'
            && $context['body']['data']['access_token'] === '***MASKED***'
            && $context['body']['data']['refresh_token'] === '***MASKED***'
            && is_array($context['response_body'])
            && $context['response_body']['data']['user']['access_token'] === '***MASKED***'
            && $context['response_body']['data']['user']['refresh_token'] === '***MASKED***';
    })->once();
});
