# SNS (Simple Notification Service) — LocalStack + Laravel

Amazon SNS is a pub/sub messaging service. It lets you publish a single message to a **topic**, and SNS fans it out to all subscribers — which can be SQS queues, Lambda functions, HTTP endpoints, email addresses, or mobile push notifications.

Unlike SQS (one sender → one consumer), SNS is one-to-many: one published event can trigger multiple independent consumers simultaneously.

---

## What SNS is used for

- Broadcasting events across multiple services (e.g. `order.placed` → billing + inventory + email)
- Sending push notifications to Flutter app users (via SNS Mobile Push)
- Triggering multiple SQS queues from a single event (fan-out pattern)
- Decoupling microservices that need to react to the same event

---

## 1. Install the AWS SDK (if not already present)

```bash
docker compose exec app composer require aws/aws-sdk-php
```

---

## 2. Environment variables (`.env`)

```env
AWS_ACCESS_KEY_ID=local
AWS_SECRET_ACCESS_KEY=local
AWS_DEFAULT_REGION=us-east-1
AWS_ENDPOINT=http://localstack:4566

SNS_ARN_PREFIX=arn:aws:sns:us-east-1:000000000000:
```

---

## 3. Configure `config/services.php`

```php
'sns' => [
    'key'        => env('AWS_ACCESS_KEY_ID'),
    'secret'     => env('AWS_SECRET_ACCESS_KEY'),
    'region'     => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'endpoint'   => env('AWS_ENDPOINT'),
    'arn_prefix' => env('SNS_ARN_PREFIX', 'arn:aws:sns:us-east-1:000000000000:'),
],
```

---

## 4. Create a SNS service class

Create `app/Services/SnsService.php`:

```php
<?php

namespace App\Services;

use Aws\Sns\SnsClient;

class SnsService
{
    protected SnsClient $client;

    public function __construct()
    {
        $this->client = new SnsClient([
            'version'     => 'latest',
            'region'      => config('services.sns.region', 'us-east-1'),
            'endpoint'    => config('services.sns.endpoint'),
            'credentials' => [
                'key'    => config('services.sns.key'),
                'secret' => config('services.sns.secret'),
            ],
        ]);
    }

    public function publish(string $topicArn, string $message, string $subject = ''): array
    {
        $result = $this->client->publish([
            'TopicArn' => $topicArn,
            'Message'  => $message,
            'Subject'  => $subject,
        ]);

        return $result->toArray();
    }

    public function subscribe(string $topicArn, string $protocol, string $endpoint): array
    {
        $result = $this->client->subscribe([
            'TopicArn'               => $topicArn,
            'Protocol'               => $protocol,
            'Endpoint'               => $endpoint,
            'ReturnSubscriptionArn'  => true,
        ]);

        return $result->toArray();
    }

    public function listTopics(): array
    {
        return $this->client->listTopics()->toArray();
    }

    public function listSubscriptions(): array
    {
        return $this->client->listSubscriptions()->toArray();
    }
}
```

---

## 5. Create the topic

```bash
docker compose exec localstack awslocal sns create-topic --name notifications

# Expected output:
# {
#     "TopicArn": "arn:aws:sns:us-east-1:000000000000:notifications"
# }
```

---

## 6. Clear config and test basic publish

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan tinker
```

```php
$sns = new \App\Services\SnsService();

// List topics
$sns->listTopics();

// Publish a message
$result = $sns->publish(
    'arn:aws:sns:us-east-1:000000000000:notifications',
    json_encode(['event' => 'user.registered', 'userId' => 1]),
    'User Registered'
);

// Check MessageId was returned
$result['MessageId'];
```

---

## 7. SNS → SQS fan-out (real-world pattern)

This is the most common production pattern: SNS publishes an event, and multiple SQS queues receive it independently.

### Create a dedicated SQS queue for the subscription

```bash
docker compose exec localstack awslocal sqs create-queue --queue-name sns-notifications

# Get its ARN
docker compose exec localstack awslocal sqs get-queue-attributes \
    --queue-url http://localstack:4566/000000000000/sns-notifications \
    --attribute-names QueueArn
```

### Subscribe the SQS queue to the SNS topic

```bash
docker compose exec localstack awslocal sns subscribe \
    --topic-arn arn:aws:sns:us-east-1:000000000000:notifications \
    --protocol sqs \
    --notification-endpoint arn:aws:sqs:us-east-1:000000000000:sns-notifications
```

### Publish and verify fan-out

```bash
docker compose exec app php artisan tinker
```

```php
$sns = new \App\Services\SnsService();

// Publish one event to SNS
$sns->publish(
    'arn:aws:sns:us-east-1:000000000000:notifications',
    json_encode(['event' => 'order.placed', 'orderId' => 42]),
    'Order Placed'
);

// Verify it arrived in SQS via fan-out
$sqs = new \Aws\Sqs\SqsClient([
    'version'     => 'latest',
    'region'      => 'us-east-1',
    'endpoint'    => 'http://localstack:4566',
    'credentials' => ['key' => 'local', 'secret' => 'local'],
]);

$messages = $sqs->receiveMessage([
    'QueueUrl'            => 'http://localstack:4566/000000000000/sns-notifications',
    'MaxNumberOfMessages' => 1,
]);

// Shows the SNS message wrapped in an SQS envelope
$messages->get('Messages');
```

---

## 8. Verify on LocalStack side

```bash
# List all topics
docker compose exec localstack awslocal sns list-topics

# List all subscriptions
docker compose exec localstack awslocal sns list-subscriptions

# Receive message directly from the fan-out queue
docker compose exec localstack awslocal sqs receive-message \
    --queue-url http://localstack:4566/000000000000/sns-notifications
```

---

## SNS vs SQS — quick comparison

| | SQS | SNS |
|---|---|---|
| Pattern | Point-to-point (one consumer) | Pub/Sub (many consumers) |
| Delivery | Pull (consumer polls) | Push (SNS delivers) |
| Use case | Background job queue | Event broadcasting |
| Typical combo | Used alone for jobs | SNS topic → multiple SQS queues |

---

## Common errors

| Error | Cause | Fix |
|-------|-------|-----|
| `InvalidClientTokenId` | SDK hitting real AWS | Pass `endpoint` to `SnsClient` constructor |
| `Topic does not exist` | Topic not created | Run `awslocal sns create-topic --name notifications` |
| Fan-out message not in SQS | Queue not subscribed to topic | Run `awslocal sns subscribe` |
| Empty `MessageId` | Publish silently failed | Check endpoint in `SnsClient` config |