# SQS (Simple Queue Service) — LocalStack + Laravel

Amazon SQS is a managed message queue service. It decouples parts of your application by letting one part send a message to a queue, and another part process it asynchronously — without the sender waiting for the work to finish.

In Laravel, SQS is used as a queue driver, which means jobs dispatched with `dispatch()` are sent to SQS instead of being processed synchronously or stored in Redis/database.

---

## What SQS is used for

- Processing background jobs (sending emails, resizing images, syncing data)
- Decoupling Flutter API responses from heavy server-side work
- Retrying failed operations automatically
- Rate-limiting expensive tasks (e.g. third-party API calls)

---

## 1. Environment variables (`.env`)

```env
QUEUE_CONNECTION=sqs

AWS_ACCESS_KEY_ID=local
AWS_SECRET_ACCESS_KEY=local
AWS_DEFAULT_REGION=us-east-1
AWS_ENDPOINT=http://localstack:4566

SQS_PREFIX=http://localstack:4566/000000000000
SQS_QUEUE=default
```

---

## 2. Configure `config/queue.php`

```php
'sqs' => [
    'driver'       => 'sqs',
    'key'          => env('AWS_ACCESS_KEY_ID'),
    'secret'       => env('AWS_SECRET_ACCESS_KEY'),
    'prefix'       => env('SQS_PREFIX', 'http://localstack:4566/000000000000'),
    'queue'        => env('SQS_QUEUE', 'default'),
    'region'       => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'endpoint'     => env('AWS_ENDPOINT'),
    'after_commit' => false,
],
```

---

## 3. Create the queue

```bash
docker compose exec localstack awslocal sqs create-queue --queue-name default

# Expected output:
# {
#     "QueueUrl": "http://sqs.us-east-1.localhost.localstack.cloud:4566/000000000000/default"
# }
```

---

## 4. Custom SQS connector (required)

Laravel's built-in SQS connector ignores the `endpoint` key and always hits the real AWS. Override it with a custom connector.

Create `app/Queue/LocalStackSqsConnector.php`:

```php
<?php

namespace App\Queue;

use Aws\Sqs\SqsClient;
use Illuminate\Queue\Connectors\SqsConnector;

class LocalStackSqsConnector extends SqsConnector
{
    public function connect(array $config): \Illuminate\Contracts\Queue\Queue
    {
        $config = $this->getDefaultConfiguration($config);

        $sqsConfig = [
            'version'     => 'latest',
            'region'      => $config['region'],
            'credentials' => [
                'key'    => $config['key'],
                'secret' => $config['secret'],
            ],
        ];

        if (! empty($config['endpoint'])) {
            $sqsConfig['endpoint'] = $config['endpoint'];
        }

        return new \Illuminate\Queue\SqsQueue(
            new SqsClient($sqsConfig),
            $config['queue'],
            $config['prefix'] ?? '',
            $config['suffix'] ?? '',
            $config['after_commit'] ?? null
        );
    }
}
```

Register it in `app/Providers/AppServiceProvider.php`:

```php
use App\Queue\LocalStackSqsConnector;

public function boot(): void
{
    $this->app['queue']->addConnector('sqs', function () {
        return new LocalStackSqsConnector();
    });
}
```

---

## 5. Clear config and test

```bash
docker compose exec app php artisan config:clear
```

```bash
docker compose exec app php artisan tinker
```

```php
// Verify config
config('queue.connections.sqs.endpoint') // must be "http://localstack:4566"

// Push a raw message
\Illuminate\Support\Facades\Queue::connection('sqs')->push('test-job', ['hello' => 'from LocalStack']);

// Receive it with raw SDK to inspect
$sqs = new \Aws\Sqs\SqsClient([
    'version'     => 'latest',
    'region'      => 'us-east-1',
    'endpoint'    => 'http://localstack:4566',
    'credentials' => ['key' => 'local', 'secret' => 'local'],
]);

$messages = $sqs->receiveMessage([
    'QueueUrl'            => 'http://localstack:4566/000000000000/default',
    'MaxNumberOfMessages' => 1,
]);

$messages->get('Messages');
```

---

## 6. Test with a real Laravel Job

```bash
docker compose exec app php artisan make:job TestSqsJob
```

Edit `app/Jobs/TestSqsJob.php`:

```php
public function handle(): void
{
    \Log::info('TestSqsJob handled successfully via SQS!');
}
```

Dispatch and consume:

```bash
# Terminal 1 — dispatch
docker compose exec app php artisan tinker
>>> \App\Jobs\TestSqsJob::dispatch();

# Terminal 2 — consume
docker compose exec app php artisan queue:work sqs --once -vvv
```

Expected output:
```
INFO  Processing jobs from the [default] queue.
INFO  App\Jobs\TestSqsJob ..... DONE
```

---

## Verify on LocalStack side

```bash
# List queues
docker compose exec localstack awslocal sqs list-queues

# Check queue attributes (message count, etc.)
docker compose exec localstack awslocal sqs get-queue-attributes \
    --queue-url http://localstack:4566/000000000000/default \
    --attribute-names All
```

---

## Common errors

| Error | Cause | Fix |
|-------|-------|-----|
| `InvalidClientTokenId` hitting `amazonaws.com` | Endpoint not passed to SDK | Add custom `LocalStackSqsConnector` |
| `Queue URL does not exist` | Queue not created | Run `awslocal sqs create-queue --queue-name default` |
| Jobs not consumed | Worker using wrong connection | Run `queue:work sqs` not `queue:work` |