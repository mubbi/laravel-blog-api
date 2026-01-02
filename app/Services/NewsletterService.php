<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\FilterNewsletterSubscriberDTO;
use App\Data\SubscribeNewsletterDTO;
use App\Data\UnsubscribeNewsletterDTO;
use App\Data\VerifySubscriptionDTO;
use App\Data\VerifyUnsubscriptionDTO;
use App\Events\Newsletter\NewsletterSubscriberCreatedEvent;
use App\Events\Newsletter\NewsletterSubscriberDeletedEvent;
use App\Events\Newsletter\NewsletterSubscriberUnsubscribedEvent;
use App\Events\Newsletter\NewsletterSubscriberUnsubscriptionRequestedEvent;
use App\Events\Newsletter\NewsletterSubscriberVerifiedEvent;
use App\Models\NewsletterSubscriber;
use App\Repositories\Contracts\NewsletterSubscriberRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class NewsletterService
{
    public function __construct(
        private readonly NewsletterSubscriberRepositoryInterface $newsletterSubscriberRepository
    ) {}

    /**
     * Delete a newsletter subscriber
     *
     * @throws ModelNotFoundException
     */
    public function deleteSubscriber(int $subscriberId): void
    {
        $subscriber = $this->newsletterSubscriberRepository->findOrFail($subscriberId);
        $email = $subscriber->email;
        $this->newsletterSubscriberRepository->delete($subscriberId);

        Event::dispatch(new NewsletterSubscriberDeletedEvent($subscriberId, $email));
    }

    /**
     * Get subscriber by ID
     *
     * @throws ModelNotFoundException
     */
    public function getSubscriberById(int $subscriberId): NewsletterSubscriber
    {
        return $this->newsletterSubscriberRepository->findOrFail($subscriberId);
    }

    /**
     * Get subscribers with filters
     *
     * @return LengthAwarePaginator<int, NewsletterSubscriber>
     */
    public function getSubscribers(FilterNewsletterSubscriberDTO $dto): LengthAwarePaginator
    {
        $query = $this->newsletterSubscriberRepository->query();

        if ($dto->search !== null) {
            $query->where('email', 'like', "%{$dto->search}%");
        }

        if ($dto->status !== null) {
            if ($dto->status === 'verified') {
                $query->where('is_verified', true);
            } elseif ($dto->status === 'unverified') {
                $query->where('is_verified', false);
            }
        }

        if ($dto->subscribedAtFrom !== null) {
            $query->where('created_at', '>=', $dto->subscribedAtFrom);
        }

        if ($dto->subscribedAtTo !== null) {
            $query->where('created_at', '<=', $dto->subscribedAtTo);
        }

        return $query->orderBy($dto->sortBy, $dto->sortOrder)->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    /**
     * Get total subscriber count
     */
    public function getTotalSubscribers(): int
    {
        return $this->newsletterSubscriberRepository->count();
    }

    /**
     * Generate verification token and expiration date
     *
     * @return array{token: string, hashed_token: string, expires_at: \Carbon\CarbonImmutable}
     */
    private function generateVerificationToken(): array
    {
        $verificationToken = Str::random(64);
        $hashedToken = Hash::make($verificationToken);
        /** @var mixed $expirationConfig */
        $expirationConfig = config('newsletter.verification_token_expiration');
        $expirationMinutes = (int) ($expirationConfig ?? 1440);
        $tokenExpiresAt = now()->addMinutes($expirationMinutes);

        return [
            'token' => $verificationToken,
            'hashed_token' => $hashedToken,
            'expires_at' => $tokenExpiresAt,
        ];
    }

    /**
     * Subscribe to newsletter
     */
    public function subscribe(SubscribeNewsletterDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $existingSubscriber = $this->newsletterSubscriberRepository->findByEmail($dto->email);

            if ($existingSubscriber !== null) {
                $this->resubscribeExistingSubscriber($existingSubscriber, $dto);
            } else {
                $this->createNewSubscriber($dto);
            }
        });
    }

    /**
     * Handle resubscription for existing subscriber
     */
    private function resubscribeExistingSubscriber(NewsletterSubscriber $subscriber, SubscribeNewsletterDTO $dto): void
    {
        $tokenData = $this->generateVerificationToken();
        $isVerifiedAndActive = $subscriber->is_verified && $subscriber->unsubscribed_at === null;

        $updateData = [
            'verification_token' => $tokenData['hashed_token'],
            'verification_token_expires_at' => $tokenData['expires_at'],
            'is_verified' => false,
        ];

        if ($isVerifiedAndActive) {
            // Update user_id if provided and different
            if ($dto->userId !== null && $subscriber->user_id !== $dto->userId) {
                $updateData['user_id'] = $dto->userId;
            }
        } else {
            // For unverified or unsubscribed, reset subscription
            $updateData['user_id'] = $dto->userId ?? $subscriber->user_id;
            $updateData['unsubscribed_at'] = null;
            $updateData['subscribed_at'] = now();
        }

        $this->newsletterSubscriberRepository->update($subscriber->id, $updateData);
        $this->dispatchSubscriberCreatedEvent($subscriber->id, $dto->email, $tokenData['token']);
    }

    /**
     * Create a new newsletter subscriber
     */
    private function createNewSubscriber(SubscribeNewsletterDTO $dto): void
    {
        $tokenData = $this->generateVerificationToken();

        $subscriberData = array_merge($dto->toArray(), [
            'verification_token' => $tokenData['hashed_token'],
            'verification_token_expires_at' => $tokenData['expires_at'],
            'is_verified' => false,
            'subscribed_at' => now(),
        ]);

        $subscriber = $this->newsletterSubscriberRepository->create($subscriberData);
        $this->dispatchSubscriberCreatedEvent($subscriber->id, $dto->email, $tokenData['token']);
    }

    /**
     * Dispatch NewsletterSubscriberCreatedEvent
     */
    private function dispatchSubscriberCreatedEvent(int $subscriberId, string $email, string $token): void
    {
        Event::dispatch(new NewsletterSubscriberCreatedEvent($subscriberId, $email, $token));
    }

    /**
     * Verify newsletter subscription
     *
     * @throws ModelNotFoundException
     */
    public function verifySubscription(VerifySubscriptionDTO $dto): NewsletterSubscriber
    {
        return DB::transaction(function () use ($dto): NewsletterSubscriber {
            $subscriber = $this->newsletterSubscriberRepository->findByVerificationTokenAndEmail($dto->token, $dto->email);

            if ($subscriber === null) {
                $exception = new ModelNotFoundException;
                $exception->setModel(NewsletterSubscriber::class);
                throw $exception;
            }

            // Check if token is expired
            if ($subscriber->verification_token_expires_at !== null && $subscriber->verification_token_expires_at->isPast()) {
                $exception = new ModelNotFoundException;
                $exception->setModel(NewsletterSubscriber::class);
                throw $exception;
            }

            // Already verified
            if ($subscriber->is_verified) {
                return $subscriber;
            }

            // Verify the subscriber
            $this->newsletterSubscriberRepository->update($subscriber->id, [
                'is_verified' => true,
                'verification_token' => null,
                'verification_token_expires_at' => null,
            ]);

            // Refresh the model to get updated data
            $subscriber->refresh();

            Event::dispatch(new NewsletterSubscriberVerifiedEvent(
                $subscriber->id,
                $subscriber->email
            ));

            return $subscriber;
        });
    }

    /**
     * Request unsubscribe from newsletter
     * Generates a verification token and sends it via email
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function unsubscribe(UnsubscribeNewsletterDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            $subscriber = $this->newsletterSubscriberRepository->findByEmail($dto->email);

            if ($subscriber === null) {
                $exception = new ModelNotFoundException;
                $exception->setModel(NewsletterSubscriber::class);
                throw $exception;
            }

            // Check if subscriber is already unsubscribed
            if ($subscriber->unsubscribed_at !== null) {
                throw ValidationException::withMessages([
                    'email' => [__('newsletter.subscriber_already_unsubscribed')],
                ]);
            }

            // Check if subscriber is verified
            if (! $subscriber->is_verified) {
                throw ValidationException::withMessages([
                    'email' => [__('newsletter.subscriber_not_verified')],
                ]);
            }

            // Generate token and send email
            $tokenData = $this->generateVerificationToken();
            $this->newsletterSubscriberRepository->update($subscriber->id, [
                'verification_token' => $tokenData['hashed_token'],
                'verification_token_expires_at' => $tokenData['expires_at'],
            ]);

            $this->dispatchUnsubscriptionRequestedEvent($subscriber->id, $dto->email, $tokenData['token']);
        });
    }

    /**
     * Dispatch NewsletterSubscriberUnsubscriptionRequestedEvent
     */
    private function dispatchUnsubscriptionRequestedEvent(int $subscriberId, string $email, string $token): void
    {
        Event::dispatch(new NewsletterSubscriberUnsubscriptionRequestedEvent($subscriberId, $email, $token));
    }

    /**
     * Verify newsletter unsubscription
     *
     * @throws ModelNotFoundException
     */
    public function verifyUnsubscription(VerifyUnsubscriptionDTO $dto): NewsletterSubscriber
    {
        return DB::transaction(function () use ($dto): NewsletterSubscriber {
            $subscriber = $this->newsletterSubscriberRepository->findByVerificationTokenAndEmail($dto->token, $dto->email);

            if ($subscriber === null) {
                $exception = new ModelNotFoundException;
                $exception->setModel(NewsletterSubscriber::class);
                throw $exception;
            }

            // Check if token is expired
            if ($subscriber->verification_token_expires_at !== null && $subscriber->verification_token_expires_at->isPast()) {
                $exception = new ModelNotFoundException;
                $exception->setModel(NewsletterSubscriber::class);
                throw $exception;
            }

            // Already unsubscribed
            if ($subscriber->unsubscribed_at !== null) {
                // Clear token even if already unsubscribed
                $this->newsletterSubscriberRepository->update($subscriber->id, [
                    'verification_token' => null,
                    'verification_token_expires_at' => null,
                ]);
                $subscriber->refresh();

                return $subscriber;
            }

            // Mark as unsubscribed
            $this->newsletterSubscriberRepository->update($subscriber->id, [
                'unsubscribed_at' => now(),
                'is_verified' => false,
                'verification_token' => null,
                'verification_token_expires_at' => null,
            ]);

            // Refresh the model to get updated data
            $subscriber->refresh();

            Event::dispatch(new NewsletterSubscriberUnsubscribedEvent(
                $subscriber->id,
                $subscriber->email
            ));

            return $subscriber;
        });
    }
}
