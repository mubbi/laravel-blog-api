<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Helper class for common utility functions
 */
final class Helper
{
    /**
     * Get the real client IP address, considering proxies, load balancers, and VPNs.
     *
     * Checks various headers in order of priority:
     * 1. CF-Connecting-IP (Cloudflare)
     * 2. True-Client-IP (Cloudflare Enterprise, Akamai)
     * 3. X-Forwarded-For (standard proxy header - takes first IP)
     * 4. X-Real-IP (Nginx proxy)
     * 5. X-Client-IP (Apache)
     * 6. X-Forwarded (standard proxy header)
     * 7. X-Cluster-Client-IP (Amazon ELB)
     * 8. Request::ip() (Laravel's default - falls back to REMOTE_ADDR)
     *
     * @param  Request  $request  The request instance
     * @return string The real client IP address
     */
    public static function getRealIpAddress(Request $request): string
    {
        // Cloudflare
        $ip = $request->header('CF-Connecting-IP');
        if ($ip !== null && self::isValidIp($ip)) {
            return $ip;
        }

        // Cloudflare Enterprise / Akamai
        $ip = $request->header('True-Client-IP');
        if ($ip !== null && self::isValidIp($ip)) {
            return $ip;
        }

        // X-Forwarded-For (can contain multiple IPs, comma-separated)
        // Take the first one which is the original client IP
        $xForwardedFor = $request->header('X-Forwarded-For');
        if ($xForwardedFor !== null) {
            $ips = explode(',', $xForwardedFor);
            $ip = trim($ips[0]);
            if (self::isValidIp($ip)) {
                return $ip;
            }
        }

        // Nginx proxy
        $ip = $request->header('X-Real-IP');
        if ($ip !== null && self::isValidIp($ip)) {
            return $ip;
        }

        // Apache
        $ip = $request->header('X-Client-IP');
        if ($ip !== null && self::isValidIp($ip)) {
            return $ip;
        }

        // Standard proxy header
        $xForwarded = $request->header('X-Forwarded');
        if ($xForwarded !== null) {
            $ips = explode(',', $xForwarded);
            $ip = trim($ips[0]);
            if (self::isValidIp($ip)) {
                return $ip;
            }
        }

        // Amazon ELB
        $ip = $request->header('X-Cluster-Client-IP');
        if ($ip !== null && self::isValidIp($ip)) {
            return $ip;
        }

        // Fallback to Laravel's default IP detection
        $ip = $request->ip();
        if ($ip === null) {
            return '0.0.0.0';
        }

        return $ip;
    }

    /**
     * Validate if a string is a valid IP address (IPv4 or IPv6).
     *
     * Filters out private/internal IPs if needed, but for logging purposes,
     * we might want to keep them. This function validates format only.
     *
     * @param  string  $ip  The IP address to validate
     * @return bool True if valid IP, false otherwise
     */
    private static function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) !== false;
    }
}
