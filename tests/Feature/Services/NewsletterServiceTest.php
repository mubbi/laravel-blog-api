<?php

declare(strict_types=1);

use App\Data\Newsletter\FilterNewsletterSubscriberDTO;
use App\Data\Newsletter\SubscribeNewsletterDTO;
use App\Data\Newsletter\UnsubscribeNewsletterDTO;
use App\Data\Newsletter\VerifySubscriptionDTO;
use App\Data\Newsletter\VerifyUnsubscriptionDTO;
use App\Events\Newsletter\NewsletterSubscriberCreatedEvent;
use App\Events\Newsletter\NewsletterSubscriberDeletedEvent;
use App\Events\Newsletter\NewsletterSubscriberUnsubscribedEvent;
use App\Events\Newsletter\NewsletterSubscriberUnsubscriptionRequestedEvent;
use App\Events\Newsletter\NewsletterSubscriberVerifiedEvent;
use App\Models\NewsletterSubscriber;
use App\Services\NewsletterService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

describe('NewsletterService', function () {
    beforeEach(function () {
        $this->service = app(NewsletterService::class);
    });

    describe('subscribe', function () {
        it('creates a new subscriber successfully', function () {
            // Arrange
            Event::fake();
            $dto = new SubscribeNewsletterDTO(
                email: 'test@example.com'
            );

            // Act
            $this->service->subscribe($dto);

            // Assert
            $this->assertDatabaseHas('newsletter_subscribers', [
                'email' => 'test@example.com',
                'is_verified' => false,
            ]);
            Event::assertDispatched(NewsletterSubscriberCreatedEvent::class);
        });

        it('resubscribes existing unsubscribed subscriber', function () {
            // Arrange
            Event::fake();
            $subscriber = NewsletterSubscriber::factory()->create([
                'email' => 'test@example.com',
                'unsubscribed_at' => now(),
            ]);
            $dto = new SubscribeNewsletterDTO(
                email: 'test@example.com'
            );

            // Act
            $this->service->subscribe($dto);

            // Assert
            $subscriber->refresh();
            expect($subscriber->unsubscribed_at)->toBeNull();
            Event::assertDispatched(NewsletterSubscriberCreatedEvent::class);
        });
    });

    describe('verifySubscription', function () {
        it('verifies subscription successfully', function () {
            // Arrange
            Event::fake();
            $subscriber = NewsletterSubscriber::factory()->create([
                'email' => 'test@example.com',
                'is_verified' => false,
                'verification_token' => \Illuminate\Support\Facades\Hash::make('token123'),
                'verification_token_expires_at' => now()->addHours(24),
            ]);

            // Mock the findByVerificationTokenAndEmail method
            $this->mock(\App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface::class, function ($mock) use ($subscriber) {
                $mock->shouldReceive('findByVerificationTokenAndEmail')
                    ->andReturn($subscriber);
                $mock->shouldReceive('update');
            });

            $dto = new VerifySubscriptionDTO(
                token: 'token123',
                email: 'test@example.com'
            );

            // Act
            $result = $this->service->verifySubscription($dto);

            // Assert
            expect($result->is_verified)->toBeTrue();
            Event::assertDispatched(NewsletterSubscriberVerifiedEvent::class);
        });

        it('throws exception when token is invalid', function () {
            // Arrange
            $this->mock(\App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface::class, function ($mock) {
                $mock->shouldReceive('findByVerificationTokenAndEmail')
                    ->andReturn(null);
            });

            $dto = new VerifySubscriptionDTO(
                token: 'invalid',
                email: 'test@example.com'
            );

            // Act & Assert
            expect(fn () => $this->service->verifySubscription($dto))
                ->toThrow(ModelNotFoundException::class);
        });
    });

    describe('unsubscribe', function () {
        it('requests unsubscription successfully', function () {
            // Arrange
            Event::fake();
            $subscriber = NewsletterSubscriber::factory()->create([
                'email' => 'test@example.com',
                'is_verified' => true,
                'unsubscribed_at' => null,
            ]);

            $dto = new UnsubscribeNewsletterDTO(
                email: 'test@example.com'
            );

            // Act
            $this->service->unsubscribe($dto);

            // Assert
            Event::assertDispatched(NewsletterSubscriberUnsubscriptionRequestedEvent::class);
        });

        it('throws exception when already unsubscribed', function () {
            // Arrange
            NewsletterSubscriber::factory()->create([
                'email' => 'test@example.com',
                'unsubscribed_at' => now(),
            ]);

            $dto = new UnsubscribeNewsletterDTO(
                email: 'test@example.com'
            );

            // Act & Assert
            expect(fn () => $this->service->unsubscribe($dto))
                ->toThrow(ValidationException::class);
        });
    });

    describe('verifyUnsubscription', function () {
        it('verifies unsubscription successfully', function () {
            // Arrange
            Event::fake();
            $subscriber = NewsletterSubscriber::factory()->create([
                'email' => 'test@example.com',
                'is_verified' => true,
                'unsubscribed_at' => null,
                'verification_token' => \Illuminate\Support\Facades\Hash::make('token123'),
                'verification_token_expires_at' => now()->addHours(24),
            ]);

            $this->mock(\App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface::class, function ($mock) use ($subscriber) {
                $mock->shouldReceive('findByVerificationTokenAndEmail')
                    ->andReturn($subscriber);
                $mock->shouldReceive('update');
            });

            $dto = new VerifyUnsubscriptionDTO(
                token: 'token123',
                email: 'test@example.com'
            );

            // Act
            $result = $this->service->verifyUnsubscription($dto);

            // Assert
            expect($result->unsubscribed_at)->not->toBeNull();
            Event::assertDispatched(NewsletterSubscriberUnsubscribedEvent::class);
        });
    });

    describe('getSubscribers', function () {
        it('can get paginated subscribers', function () {
            // Arrange
            NewsletterSubscriber::factory()->count(20)->create();

            $dto = new FilterNewsletterSubscriberDTO(
                page: 1,
                perPage: 10
            );

            // Act
            $result = $this->service->getSubscribers($dto);

            // Assert
            expect($result->count())->toBe(10);
            expect($result->total())->toBe(20);
        });

        it('can filter subscribers by search', function () {
            // Arrange
            NewsletterSubscriber::factory()->create(['email' => 'test@example.com']);
            NewsletterSubscriber::factory()->create(['email' => 'other@example.com']);

            $dto = new FilterNewsletterSubscriberDTO(
                search: 'test'
            );

            // Act
            $result = $this->service->getSubscribers($dto);

            // Assert
            expect($result->total())->toBe(1);
        });

        it('can filter subscribers by status', function () {
            // Arrange
            NewsletterSubscriber::factory()->create(['is_verified' => true]);
            NewsletterSubscriber::factory()->create(['is_verified' => false]);

            $dto = new FilterNewsletterSubscriberDTO(
                status: 'verified'
            );

            // Act
            $result = $this->service->getSubscribers($dto);

            // Assert
            expect($result->total())->toBe(1);
        });
    });

    describe('deleteSubscriber', function () {
        it('deletes a subscriber successfully', function () {
            // Arrange
            Event::fake();
            $subscriber = NewsletterSubscriber::factory()->create();

            // Act
            $this->service->deleteSubscriber($subscriber);

            // Assert
            $this->assertDatabaseMissing('newsletter_subscribers', ['id' => $subscriber->id]);
            Event::assertDispatched(NewsletterSubscriberDeletedEvent::class);
        });
    });

    describe('getSubscriberById', function () {
        it('can get subscriber by id', function () {
            // Arrange
            $subscriber = NewsletterSubscriber::factory()->create();

            // Act
            $result = $this->service->getSubscriberById($subscriber->id);

            // Assert
            expect($result->id)->toBe($subscriber->id);
        });

        it('throws exception when subscriber does not exist', function () {
            // Act & Assert
            expect(fn () => $this->service->getSubscriberById(99999))
                ->toThrow(ModelNotFoundException::class);
        });
    });

    describe('getTotalSubscribers', function () {
        it('returns total subscriber count', function () {
            // Arrange
            NewsletterSubscriber::factory()->count(5)->create();

            // Act
            $result = $this->service->getTotalSubscribers();

            // Assert
            expect($result)->toBe(5);
        });
    });
});
