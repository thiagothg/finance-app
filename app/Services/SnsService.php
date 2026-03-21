<?php

namespace App\Services;

use Aws\Sns\SnsClient;

class SnsService
{
    protected SnsClient $client;

    public function __construct()
    {
        $this->client = new SnsClient([
            'version' => 'latest',
            'region' => config('services.sns.region', env('AWS_DEFAULT_REGION', 'us-east-1')),
            'endpoint' => config('services.sns.endpoint', env('AWS_ENDPOINT')),
            'credentials' => [
                'key' => config('services.sns.key', env('AWS_ACCESS_KEY_ID')),
                'secret' => config('services.sns.secret', env('AWS_SECRET_ACCESS_KEY')),
            ],
        ]);
    }

    public function publish(string $topicArn, string $message, string $subject = ''): array
    {
        $result = $this->client->publish([
            'TopicArn' => $topicArn,
            'Message' => $message,
            'Subject' => $subject,
        ]);

        return $result->toArray();
    }

    public function subscribe(string $topicArn, string $protocol, string $endpoint): array
    {
        $result = $this->client->subscribe([
            'TopicArn' => $topicArn,
            'Protocol' => $protocol,
            'Endpoint' => $endpoint,
            'ReturnSubscriptionArn' => true,
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
