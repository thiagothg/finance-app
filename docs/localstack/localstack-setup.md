# LocalStack Setup — Laravel + Docker

LocalStack is a fully functional local AWS cloud stack. It lets you develop and test AWS-dependent features (S3, SQS, SES, SNS, and more) entirely on your machine — no AWS account, no credentials, no costs, no risk of touching production.

It runs as a Docker container and exposes a single endpoint (`http://localstack:4566`) that mimics the real AWS API, so your Laravel code doesn't need to change between local and production.

---

## Prerequisites

- Docker + Docker Compose
- Laravel project with an existing `compose.yml`
- `league/flysystem-aws-s3-v3` and `aws/aws-sdk-php` packages

---

## 1. Add LocalStack to `compose.yml`

```yaml
services:
    localstack:
        image: 'localstack/localstack:latest'
        ports:
            - '${FORWARD_LOCALSTACK_PORT:-4566}:4566'
        environment:
            SERVICES: '${LOCALSTACK_SERVICES:-s3,sqs,ses,sns}'
            DEBUG: '${LOCALSTACK_DEBUG:-0}'
            PERSISTENCE: '1'
            AWS_DEFAULT_REGION: '${AWS_DEFAULT_REGION:-us-east-1}'
        volumes:
            - 'sail-localstack:/var/lib/localstack'
            - '/var/run/docker.sock:/var/run/docker.sock'
            - './docker/localstack/init.sh:/etc/localstack/init/ready.d/init.sh'
        networks:
            - sail
        healthcheck:
            test:
                - CMD
                - bash
                - '-c'
                - 'awslocal s3 ls'
            retries: 3
            timeout: 5s

volumes:
    sail-localstack:
        driver: local
```

---

## 2. Bootstrap script

Create `docker/localstack/init.sh` — runs automatically on container start:

```bash
#!/bin/bash
echo "==> Creating S3 bucket..."
awslocal s3 mb s3://${AWS_BUCKET:-your-bucket-name}
awslocal s3api put-bucket-acl \
    --bucket ${AWS_BUCKET:-your-bucket-name} \
    --acl public-read

echo "==> Creating SQS queue..."
awslocal sqs create-queue --queue-name default

echo "==> Creating SNS topic..."
awslocal sns create-topic --name notifications

echo "✅ LocalStack bootstrap complete."
```

Make it executable:

```bash
chmod +x docker/localstack/init.sh
```

---

## 3. Environment variables (`.env`)

```env
AWS_ACCESS_KEY_ID=local
AWS_SECRET_ACCESS_KEY=local
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_ENDPOINT=http://localstack:4566        # Must use service name, NOT 127.0.0.1
AWS_USE_PATH_STYLE_ENDPOINT=true

LOCALSTACK_SERVICES=s3,sqs,ses,sns
```

> ⚠️ Always use `http://localstack:4566` — using `127.0.0.1` inside the app container points to itself, not LocalStack.

---

## 4. Install required packages

```bash
docker compose exec app composer require league/flysystem-aws-s3-v3 "^3.0"
docker compose exec app composer require aws/aws-sdk-php
```

---

## 5. Verify LocalStack is running

```bash
# Check health
docker compose exec app curl -s http://localstack:4566/_localstack/health | python3 -m json.tool

# Expected output includes:
# "s3": "running",
# "sqs": "running",
# "ses": "running",
# "sns": "running"
```

---

## Useful commands

```bash
# Restart and re-run init script
docker compose restart localstack

# Open a shell inside LocalStack
docker compose exec localstack bash

# Run awslocal commands directly
docker compose exec localstack awslocal s3 ls
docker compose exec localstack awslocal sqs list-queues
docker compose exec localstack awslocal sns list-topics
```