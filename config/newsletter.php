<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Newsletter Verification Token Expiration
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until a newsletter verification
    | token will be considered expired. Default is 24 hours (1440 minutes).
    | After expiration, users will need to request a new verification token.
    |
    */

    'verification_token_expiration' => env('NEWSLETTER_VERIFICATION_TOKEN_EXPIRATION', 1440),

];
