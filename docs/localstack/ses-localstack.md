# SES (Simple Email Service) — LocalStack + Laravel

Amazon SES is a cloud-based email sending service. It's designed for sending transactional emails (password resets, order confirmations, notifications) and marketing emails at scale, with high deliverability and detailed sending statistics.

In Laravel, SES replaces SMTP as the mail transport driver. Instead of connecting to an SMTP server, Laravel hands emails directly to the AWS SDK, which delivers them through SES.

---

## What SES is used for

- Sending transactional emails (welcome, reset password, invoice)
- Pushing notifications to Flutter app users via email
- Email delivery tracking (bounces, complaints, opens)
- Bulk email campaigns

---

## 1. Install the Symfony SES transport

```bash
docker compose exec app composer require symfony/amazon-mailer
```

---

## 2. Environment variables (`.env`)

```env
MAIL_MAILER=ses-local

AWS_ACCESS_KEY_ID=local
AWS_SECRET_ACCESS_KEY=local
AWS_DEFAULT_REGION=us-east-1
AWS_ENDPOINT=http://localstack:4566
```

---

## 3. Configure `config/services.php`

```php
'ses' => [
    'key'      => env('AWS_ACCESS_KEY_ID'),
    'secret'   => env('AWS_SECRET_ACCESS_KEY'),
    'region'   => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'endpoint' => env('AWS_ENDPOINT'),
    'options'  => [
        'endpoint' => env('AWS_ENDPOINT'),
    ],
],
```

---

## 4. Register a custom SES transport

Laravel's built-in SES transport does not forward the `endpoint` to LocalStack. Register a custom one in `app/Providers/AppServiceProvider.php`:

```php
use Aws\Ses\SesClient;
use Illuminate\Mail\Transport\SesTransport;

public function boot(): void
{
    \Illuminate\Support\Facades\Mail::extend('ses-local', function () {
        $sesClient = new SesClient([
            'version'     => 'latest',
            'region'      => config('services.ses.region'),
            'endpoint'    => config('services.ses.endpoint'),
            'credentials' => [
                'key'    => config('services.ses.key'),
                'secret' => config('services.ses.secret'),
            ],
        ]);

        return new SesTransport($sesClient, []);
    });
}
```

---

## 5. Verify a sender identity

SES requires a verified sender email before it will accept messages. LocalStack simulates this:

```bash
# Verify sender address
docker compose exec localstack awslocal ses verify-email-identity \
    --email-address noreply@yourdomain.com

# Confirm it's listed
docker compose exec localstack awslocal ses list-identities
```

---

## 6. Clear config and test

```bash
docker compose exec app php artisan config:clear
```

```bash
docker compose exec app php artisan tinker
```

```php
// Verify config
config('services.ses')

// Send a test email
\Illuminate\Support\Facades\Mail::raw('Hello from LocalStack SES!', function ($msg) {
    $msg->to('test@example.com')
        ->from('noreply@yourdomain.com')
        ->subject('LocalStack SES Test');
});
```

---

## 7. Verify delivery on LocalStack side

```bash
# Check send statistics
docker compose exec localstack awslocal ses get-send-statistics

# List verified identities
docker compose exec localstack awslocal ses list-identities

# Check sending quota
docker compose exec localstack awslocal ses get-send-quota
```

---

## Using a Mailable class

```bash
docker compose exec app php artisan make:mail WelcomeMail
```

```php
// app/Mail/WelcomeMail.php
public function envelope(): Envelope
{
    return new Envelope(
        from: new Address('noreply@yourdomain.com', 'My App'),
        subject: 'Welcome!',
    );
}

public function content(): Content
{
    return new Content(view: 'emails.welcome');
}
```

Dispatch:

```php
\Illuminate\Support\Facades\Mail::to('user@example.com')->send(new \App\Mail\WelcomeMail());
```

---

## Common errors

| Error | Cause | Fix |
|-------|-------|-----|
| Auth error hitting `amazonaws.com` | Endpoint not forwarded to SDK | Register custom `ses-local` transport |
| `Email address is not verified` | Sender not verified in LocalStack | Run `awslocal ses verify-email-identity` |
| `Class SesTransport not found` | Missing Symfony mailer package | `composer require symfony/amazon-mailer` |
| Mail sent but not received | Using Mailpit mailer in `.env` | Set `MAIL_MAILER=ses-local` |