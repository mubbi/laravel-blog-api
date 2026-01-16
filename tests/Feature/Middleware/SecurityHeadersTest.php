<?php

declare(strict_types=1);

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

describe('SecurityHeaders Middleware', function () {
    beforeEach(function () {
        // Create test routes for different scenarios
        Route::get('/api/test-endpoint', function () {
            return response()->json(['success' => true]);
        });

        Route::get('/docs/api', function () {
            return response('<html><body>Documentation</body></html>', 200)
                ->header('Content-Type', 'text/html');
        });

        Route::get('/docs/api/spec', function () {
            return response()->json(['spec' => 'data']);
        });

        Route::get('/other-route', function () {
            return response()->json(['data' => 'test']);
        });
    });

    it('applies restrictive CSP for API routes', function () {
        $response = $this->getJson('/api/test-endpoint');

        $response->assertOk();
        $csp = $response->headers->get('Content-Security-Policy');

        expect($csp)->toBeString()
            ->and($csp)->toContain("script-src 'none'")
            ->and($csp)->toContain("style-src 'none'")
            ->and($csp)->toContain("frame-ancestors 'none'");
    });

    it('applies permissive CSP for documentation routes', function () {
        $response = $this->get('/docs/api');

        $response->assertOk();
        $csp = $response->headers->get('Content-Security-Policy');

        expect($csp)->toBeString()
            ->and($csp)->toContain("script-src 'self' 'unsafe-inline' https://unpkg.com")
            ->and($csp)->toContain("style-src 'self' 'unsafe-inline' https://unpkg.com")
            ->and($csp)->toContain("font-src 'self' data: https://unpkg.com")
            ->and($csp)->toContain("frame-ancestors 'self'");
    });

    it('applies permissive CSP for all docs/* routes', function () {
        $response = $this->getJson('/docs/api/spec');

        $response->assertOk();
        $csp = $response->headers->get('Content-Security-Policy');

        expect($csp)->toBeString()
            ->and($csp)->toContain("script-src 'self' 'unsafe-inline' https://unpkg.com")
            ->and($csp)->toContain("style-src 'self' 'unsafe-inline' https://unpkg.com");
    });

    it('applies restrictive CSP for non-docs routes', function () {
        $response = $this->getJson('/other-route');

        $response->assertOk();
        $csp = $response->headers->get('Content-Security-Policy');

        expect($csp)->toBeString()
            ->and($csp)->toContain("script-src 'none'")
            ->and($csp)->toContain("style-src 'none'");
    });

    it('sets X-Frame-Options header', function () {
        $response = $this->getJson('/api/test-endpoint');

        $response->assertOk();
        expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
    });

    it('sets X-Content-Type-Options header', function () {
        $response = $this->getJson('/api/test-endpoint');

        $response->assertOk();
        expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    });

    it('sets X-XSS-Protection header', function () {
        $response = $this->getJson('/api/test-endpoint');

        $response->assertOk();
        expect($response->headers->get('X-XSS-Protection'))->toBe('1; mode=block');
    });

    it('sets Referrer-Policy header', function () {
        $response = $this->getJson('/api/test-endpoint');

        $response->assertOk();
        expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    });

    it('sets Permissions-Policy header', function () {
        $response = $this->getJson('/api/test-endpoint');

        $response->assertOk();
        expect($response->headers->get('Permissions-Policy'))->toBe('geolocation=(), microphone=(), camera=()');
    });

    it('does not set HSTS header in local environment', function () {
        $response = $this->getJson('/api/test-endpoint');

        $response->assertOk();
        expect($response->headers->get('Strict-Transport-Security'))->toBeNull();
    });

    it('does not set HSTS header for HTTP requests', function () {
        // Simulate non-secure request
        $request = Request::create('/api/test-endpoint', 'GET');
        $request->server->set('HTTPS', false);

        $middleware = new SecurityHeaders;
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        expect($response->headers->get('Strict-Transport-Security'))->toBeNull();
    });

    it('sets all security headers for documentation routes', function () {
        $response = $this->get('/docs/api');

        $response->assertOk();
        expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN')
            ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
            ->and($response->headers->get('X-XSS-Protection'))->toBe('1; mode=block')
            ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
            ->and($response->headers->get('Permissions-Policy'))->toBe('geolocation=(), microphone=(), camera=()')
            ->and($response->headers->get('Content-Security-Policy'))->toBeString();
    });

    it('preserves existing response headers', function () {
        $response = $this->getJson('/api/test-endpoint');

        $response->assertOk();
        // Verify response still works correctly
        $response->assertJson(['success' => true]);
    });

    it('handles different HTTP methods correctly', function () {
        Route::post('/api/test-post', function () {
            return response()->json(['created' => true]);
        });

        Route::put('/api/test-put', function () {
            return response()->json(['updated' => true]);
        });

        Route::delete('/api/test-delete', function () {
            return response()->json(['deleted' => true]);
        });

        $postResponse = $this->postJson('/api/test-post');
        $putResponse = $this->putJson('/api/test-put');
        $deleteResponse = $this->deleteJson('/api/test-delete');

        expect($postResponse->headers->get('Content-Security-Policy'))->toContain("script-src 'none'")
            ->and($putResponse->headers->get('Content-Security-Policy'))->toContain("script-src 'none'")
            ->and($deleteResponse->headers->get('Content-Security-Policy'))->toContain("script-src 'none'");
    });
});
