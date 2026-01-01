<?php

declare(strict_types=1);

use App\Support\Helper;
use Illuminate\Http\Request;

describe('Helper::getRealIpAddress', function () {
    it('returns CF-Connecting-IP header when present and valid', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.1',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('203.0.113.1');
    });

    it('skips CF-Connecting-IP when invalid and uses next header', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_CF_CONNECTING_IP' => 'invalid-ip',
            'HTTP_TRUE_CLIENT_IP' => '198.51.100.1',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('198.51.100.1');
    });

    it('returns True-Client-IP header when present and valid', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_TRUE_CLIENT_IP' => '198.51.100.1',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('198.51.100.1');
    });

    it('returns first IP from X-Forwarded-For header when present', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_FORWARDED_FOR' => '203.0.113.1, 198.51.100.1, 192.0.2.1',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('203.0.113.1');
    });

    it('trims whitespace from X-Forwarded-For IPs', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_FORWARDED_FOR' => '  203.0.113.1  ,  198.51.100.1  ',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('203.0.113.1');
    });

    it('skips X-Forwarded-For when first IP is invalid and uses next header', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_FORWARDED_FOR' => 'invalid-ip, 198.51.100.1',
            'HTTP_X_REAL_IP' => '192.0.2.1',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('192.0.2.1');
    });

    it('returns X-Real-IP header when present and valid', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_REAL_IP' => '192.0.2.1',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('192.0.2.1');
    });

    it('returns X-Client-IP header when present and valid', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_CLIENT_IP' => '192.0.2.2',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('192.0.2.2');
    });

    it('returns first IP from X-Forwarded header when present', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_FORWARDED' => '203.0.113.2, 198.51.100.2',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('203.0.113.2');
    });

    it('trims whitespace from X-Forwarded IPs', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_FORWARDED' => '  192.0.2.3  ,  198.51.100.3  ',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('192.0.2.3');
    });

    it('returns X-Cluster-Client-IP header when present and valid', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_CLUSTER_CLIENT_IP' => '192.0.2.4',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('192.0.2.4');
    });

    it('falls back to Request::ip() when no headers are present', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'REMOTE_ADDR' => '192.168.1.1',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('192.168.1.1');
    });

    it('returns 0.0.0.0 when Request::ip() returns null', function () {
        // Arrange
        $request = \Mockery::mock(Request::class);
        $request->shouldReceive('header')
            ->andReturn(null);
        $request->shouldReceive('ip')
            ->andReturn(null);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('0.0.0.0');
    });

    it('validates IPv6 addresses correctly', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_CF_CONNECTING_IP' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('2001:0db8:85a3:0000:0000:8a2e:0370:7334');
    });

    it('validates IPv6 addresses in compressed format', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_REAL_IP' => '2001:db8::1',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('2001:db8::1');
    });

    it('skips invalid IPv6 addresses and uses next header', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_CF_CONNECTING_IP' => 'invalid-ipv6',
            'HTTP_X_REAL_IP' => '192.0.2.5',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('192.0.2.5');
    });

    it('prioritizes headers in correct order', function () {
        // Arrange - All headers present, should use CF-Connecting-IP (highest priority)
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_CF_CONNECTING_IP' => '203.0.113.10',
            'HTTP_TRUE_CLIENT_IP' => '198.51.100.10',
            'HTTP_X_FORWARDED_FOR' => '192.0.2.10',
            'HTTP_X_REAL_IP' => '192.0.2.11',
            'HTTP_X_CLIENT_IP' => '192.0.2.12',
            'HTTP_X_FORWARDED' => '192.0.2.13',
            'HTTP_X_CLUSTER_CLIENT_IP' => '192.0.2.14',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('203.0.113.10');
    });

    it('uses True-Client-IP when CF-Connecting-IP is missing', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_TRUE_CLIENT_IP' => '198.51.100.20',
            'HTTP_X_FORWARDED_FOR' => '192.0.2.20',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('198.51.100.20');
    });

    it('handles empty X-Forwarded-For header gracefully', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_FORWARDED_FOR' => '',
            'HTTP_X_REAL_IP' => '192.0.2.30',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('192.0.2.30');
    });

    it('handles X-Forwarded-For with only whitespace', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_FORWARDED_FOR' => '   ,  ,  ',
            'HTTP_X_REAL_IP' => '192.0.2.31',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('192.0.2.31');
    });

    it('accepts private IP addresses in validation', function () {
        // Note: The current implementation accepts private IPs for logging purposes
        // This test verifies that private IPs are still considered valid format-wise
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_REAL_IP' => '10.0.0.1', // Private IP
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert - Private IPs are accepted (as per comment in code)
        expect($ip)->toBe('10.0.0.1');
    });

    it('accepts localhost IP addresses in validation', function () {
        // Note: The current implementation accepts localhost IPs
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_REAL_IP' => '127.0.0.1', // Localhost IP
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert - Localhost IPs are accepted
        expect($ip)->toBe('127.0.0.1');
    });

    it('handles single IP in X-Forwarded-For without commas', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_FORWARDED_FOR' => '203.0.113.50',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('203.0.113.50');
    });

    it('handles single IP in X-Forwarded without commas', function () {
        // Arrange
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_X_FORWARDED' => '192.0.2.51',
        ]);

        // Act
        $ip = Helper::getRealIpAddress($request);

        // Assert
        expect($ip)->toBe('192.0.2.51');
    });
});
