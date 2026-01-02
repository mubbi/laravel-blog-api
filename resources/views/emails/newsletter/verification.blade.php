<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('newsletter.verification_email_subject') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 8px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
        }
        .token-box {
            background-color: #ffffff;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            font-family: monospace;
            word-break: break-all;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ __('newsletter.verification_email_subject') }}</h1>
        
        <p>{{ __('newsletter.verification_email_greeting') }}</p>
        
        <p>{{ __('newsletter.verification_email_intro') }}</p>
        
        <p><strong>{{ __('newsletter.verification_email_token_label') }}</strong></p>
        <div class="token-box">
            {{ $verificationToken }}
        </div>
        
        <p>Use this token to verify your subscription by making a POST request to: <code>{{ $verificationUrl }}</code> with the token in the request body.</p>
        
        <p>{{ __('newsletter.verification_email_outro') }}</p>
        
        <div class="footer">
            <p>{{ __('newsletter.verification_email_expires', ['minutes' => config('newsletter.verification_token_expiration', 1440) / 60]) }}</p>
        </div>
    </div>
</body>
</html>

