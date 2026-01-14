<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Helper;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('api-logger.enabled')) {
            $response = $next($request);
            if (! ($response instanceof Response)) {
                $content = is_string($response) ? $response : (is_array($response) ? (json_encode($response) ?: '') : '');
                $response = new Response($content);
            }

            return $response;
        }

        // Get or generate a request ID for tracing
        $requestId = $request->headers->get('X-Request-Id') ?? uniqid('req_', true);
        $startTime = microtime(true);
        $response = $next($request);
        if (! ($response instanceof Response)) {
            $content = is_string($response) ? $response : (is_array($response) ? (json_encode($response) ?: '') : '');
            $response = new Response($content);
        }
        $endTime = microtime(true);

        $user = Auth::user();
        $userId = $user ? $user->id : null;
        $ip = Helper::getRealIpAddress($request);
        $method = $request->method();
        $uri = $request->getRequestUri();
        /** @var array<string, array<int, string>|string> $rawHeaders */
        $rawHeaders = $request->headers->all();
        $headers = $this->maskHeaders($rawHeaders);

        $rawBody = $request->all();
        $body = $this->maskBody($this->castArrayKeysToString($rawBody));

        $status = $response->getStatusCode();

        $responseContent = $this->getResponseContent($response);
        $responseBody = is_array($responseContent)
            ? $this->maskBody($this->castArrayKeysToString($responseContent))
            : $responseContent;
        $duration = round(($endTime - $startTime) * 1000, 2);

        Log::info(__('log.api_request'), [
            'request_id' => $requestId,
            'user_id' => $userId,
            'ip' => $ip,
            'method' => $method,
            'uri' => $uri,
            'headers' => $headers,
            'body' => $body,
            'response_status' => $status,
            'response_body' => $responseBody,
            'duration_ms' => $duration,
        ]);

        return $response;
    }

    /**
     * @param  array<mixed, mixed>  $array
     * @return array<string, mixed>
     */
    private function castArrayKeysToString(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $result[(string) $key] = $value;
        }

        return $result;
    }

    /**
     * @param  array<string, array<int, string|null>|string|null>  $headers
     * @return array<string, mixed>
     */
    protected function maskHeaders(array $headers): array
    {
        $masked = [];
        $maskKeys = (array) config('api-logger.masked_headers', []);
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $maskKeys, true)) {
                $masked[$key] = '***MASKED***';
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * Recursively mask sensitive fields in request/response body
     *
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function maskBody(array $body): array
    {
        $maskKeys = (array) config('api-logger.masked_body_keys', []);
        $masked = [];

        foreach ($body as $key => $value) {
            $lowerKey = strtolower((string) $key);
            if (in_array($lowerKey, $maskKeys, true)) {
                $masked[$key] = '***MASKED***';
            } elseif (is_array($value)) {
                /** @var array<string, mixed> $value */
                $value = $this->castArrayKeysToString($value);
                $masked[$key] = $this->maskBody($value);
            } else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * @param  Response|string|array<string, mixed>  $response
     * @return array<string, mixed>|string
     */
    protected function getResponseContent(Response|string|array $response): array|string
    {
        if ($response instanceof Response) {
            $content = $response->getContent();
            $json = is_string($content) ? json_decode($content, true) : null;

            return is_array($json) ? $this->castArrayKeysToString($json) : (is_string($content) ? $content : '');
        }

        return is_array($response) ? $this->castArrayKeysToString($response) : $response;
    }
}
