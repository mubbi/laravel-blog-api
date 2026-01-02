<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\User;
use App\Services\ExceptionHandlerService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

describe('ExceptionHandlerService', function () {
    beforeEach(function () {
        $this->service = new ExceptionHandlerService;
        $this->request = Request::create('/api/test', 'GET');
    });

    it('should not log validation exceptions', function () {
        $exception = ValidationException::withMessages([
            'email' => ['The email field is required.'],
        ]);

        $response = $this->service->handle($exception, $this->request);
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(422)
            ->and($data['status'])->toBeFalse()
            ->and($data['error'])->toBeArray();
    });

    it('should handle non-validation exceptions', function () {
        $exception = new \Exception('Test exception');

        $response = $this->service->handle($exception, $this->request);

        expect($response->getStatusCode())->toBe(500);
    });

    it('should return 404 for ModelNotFoundException', function () {
        $exception = new ModelNotFoundException;
        $exception->setModel(Article::class);

        $response = $this->service->handle($exception, $this->request);
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(404)
            ->and($data['status'])->toBeFalse()
            ->and($data['message'])->toBe(__('common.article_not_found'))
            ->and($data['data'])->toBeNull()
            ->and($data['error'])->toBeNull();
    });

    it('should return 401 for UnauthorizedException', function () {
        $exception = new UnauthorizedException('Invalid credentials');

        $response = $this->service->handle($exception, $this->request);
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(401)
            ->and($data['status'])->toBeFalse()
            ->and($data['message'])->toBe('Invalid credentials');
    });

    it('should return 403 for AuthorizationException', function () {
        $exception = new AuthorizationException('Access denied');

        $response = $this->service->handle($exception, $this->request);
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(403)
            ->and($data['status'])->toBeFalse()
            ->and($data['message'])->toBe('Access denied');
    });

    it('should return 404 for NotFoundHttpException', function () {
        $exception = new NotFoundHttpException('Route not found');

        $response = $this->service->handle($exception, $this->request);
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(404)
            ->and($data['status'])->toBeFalse()
            ->and($data['message'])->toBe(__('common.not_found'));
    });

    it('should return 500 for generic exceptions', function () {
        $exception = new \RuntimeException('Something went wrong');

        $response = $this->service->handle($exception, $this->request);
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(500)
            ->and($data['status'])->toBeFalse()
            ->and($data['message'])->toBe(__('common.something_went_wrong'));
    });

    it('should use custom message when provided', function () {
        $exception = new \Exception('Original message');

        $response = $this->service->handle($exception, $this->request, 'Custom error message');
        $data = $response->getData(true);

        expect($data['message'])->toBe('Custom error message');
    });

    it('should include validation errors in response', function () {
        $exception = ValidationException::withMessages([
            'email' => ['The email field is required.'],
            'password' => ['The password field is required.'],
        ]);

        $response = $this->service->handle($exception, $this->request);
        $data = $response->getData(true);

        expect($data['error'])->toBeArray()
            ->and($data['error']['email'])->toBeArray()
            ->and($data['error']['password'])->toBeArray();
    });

    it('should handle exceptions with request and user information', function () {
        $user = User::factory()->create();
        $this->request->setUserResolver(fn () => $user);

        $exception = new \Exception('Test exception');

        $response = $this->service->handle($exception, $this->request, null, 'TestController');

        expect($response->getStatusCode())->toBe(500);
    });

    it('should handle exceptions with request data', function () {
        $this->request->merge([
            'email' => 'test@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'token' => 'abc123',
        ]);

        $exception = new \Exception('Test exception');

        $response = $this->service->handle($exception, $this->request);

        expect($response->getStatusCode())->toBe(500);
    });

    it('should determine if exceptions should be logged', function () {
        // During tests, logging is disabled for performance
        $modelException = new ModelNotFoundException;
        $modelException->setModel(Article::class);
        expect($this->service->shouldLogException($modelException))->toBeFalse();

        $authException = new UnauthorizedException('Invalid');
        expect($this->service->shouldLogException($authException))->toBeFalse();

        // ValidationException should not log (even outside tests)
        $validationException = ValidationException::withMessages(['field' => ['error']]);
        expect($this->service->shouldLogException($validationException))->toBeFalse();

        // Generic exception should not log during tests
        $genericException = new \Exception('Error');
        expect($this->service->shouldLogException($genericException))->toBeFalse();
    });

    it('should handle NotFoundHttpException with ModelNotFoundException previous', function () {
        $modelException = new ModelNotFoundException;
        $modelException->setModel(Article::class);
        $notFoundException = new NotFoundHttpException('Not found', $modelException);

        $response = $this->service->handle($notFoundException, $this->request);
        $data = $response->getData(true);

        expect($response->getStatusCode())->toBe(404)
            ->and($data['message'])->toBe(__('common.article_not_found'));
    });

    it('should handle ModelNotFoundException with context', function () {
        $exception = new ModelNotFoundException;
        $exception->setModel(Article::class);

        $response = $this->service->handle($exception, $this->request, null, 'TestController');

        expect($response->getStatusCode())->toBe(404);
    });

    it('should handle UnauthorizedException with context', function () {
        $exception = new UnauthorizedException('Invalid credentials');

        $response = $this->service->handle($exception, $this->request, null, 'TestController');

        expect($response->getStatusCode())->toBe(401);
    });

    it('should handle AuthorizationException with context', function () {
        $exception = new AuthorizationException('Access denied');

        $response = $this->service->handle($exception, $this->request, null, 'TestController');

        expect($response->getStatusCode())->toBe(403);
    });

    it('should handle exception without request', function () {
        $exception = new \Exception('Test exception');

        $response = $this->service->handle($exception);

        expect($response->getStatusCode())->toBe(500);
    });

    it('should handle exception without context', function () {
        $exception = new \Exception('Test exception');

        $response = $this->service->handle($exception, $this->request);

        expect($response->getStatusCode())->toBe(500);
    });
});
