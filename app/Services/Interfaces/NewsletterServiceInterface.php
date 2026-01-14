<?php

declare(strict_types=1);

namespace App\Services\Interfaces;

use App\Data\FilterNewsletterSubscriberDTO;
use App\Data\SubscribeNewsletterDTO;
use App\Data\UnsubscribeNewsletterDTO;
use App\Data\VerifySubscriptionDTO;
use App\Data\VerifyUnsubscriptionDTO;
use App\Models\NewsletterSubscriber;
use Illuminate\Pagination\LengthAwarePaginator;

interface NewsletterServiceInterface
{
    /**
     * Delete a newsletter subscriber (using route model binding)
     */
    public function deleteSubscriber(NewsletterSubscriber $newsletterSubscriber): void;

    /**
     * Get subscriber by ID
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getSubscriberById(int $subscriberId): NewsletterSubscriber;

    /**
     * Get subscribers with filters
     *
     * @return LengthAwarePaginator<int, NewsletterSubscriber>
     */
    public function getSubscribers(FilterNewsletterSubscriberDTO $dto): LengthAwarePaginator;

    /**
     * Get total subscriber count
     */
    public function getTotalSubscribers(): int;

    /**
     * Subscribe to newsletter
     */
    public function subscribe(SubscribeNewsletterDTO $dto): void;

    /**
     * Verify newsletter subscription
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function verifySubscription(VerifySubscriptionDTO $dto): NewsletterSubscriber;

    /**
     * Request unsubscribe from newsletter
     * Generates a verification token and sends it via email
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function unsubscribe(UnsubscribeNewsletterDTO $dto): void;

    /**
     * Verify newsletter unsubscription
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function verifyUnsubscription(VerifyUnsubscriptionDTO $dto): NewsletterSubscriber;
}
