# LocalStack — Production Stack Simulation

This setup mirrors the full production AWS infrastructure locally so you can
develop and test against the same services without touching real AWS.

---

## How local maps to production

| Production (AWS) | Local (LocalStack) |
|---|---|
| EC2 (app server) | Docker `app` container |
| RDS PostgreSQL | Docker `pgsql` container |
| ElastiCache Redis | Docker `redis` container |
| S3 bucket | LocalStack S3 at `http://localhost:4566` |
| CloudFront CDN | Direct S3 URL (CloudFront needs LocalStack Pro) |
| SQS main queue | LocalStack SQS `finance-app-default` |
| SQS dead-letter | LocalStack SQS `finance-app-dead-letter` |
| SNS topic | LocalStack SNS `finance-app-notifications` |
| SES email | LocalStack SES (verified locally, not delivered) |
| IAM role (no keys) | Static `key=local / secret=local` |
| Real AWS endpoints | `http://localstack:4566` (inside Docker) |

---

## Quick start

```bash
# 1. Start all services (LocalStack seed runs automatically)
docker compose up -d

# 2. Wait for LocalStack to finish seeding (~15 seconds)
docker compose logs -f localstack

# 3. Load the local .env
cp infra/localstack-sim/.env.localstack .env
php artisan config:clear

# 4. Verify everything is running correctly
bash infra/scripts/verify-localstack.sh
```

---

## Option A — Seed script (simplest)

The seed script runs automatically via the Docker volume mount:
```yaml
- './infra/scripts/localstack-seed.sh:/etc/localstack/init/ready.d/localstack-seed.sh'
```

To re-run it manually:
```bash
docker compose exec localstack \
    bash /etc/localstack/init/ready.d/localstack-seed.sh
```

---

## Option B — Terraform local (mirrors Terraform workflow)

Use `tflocal` to apply the same Terraform code against LocalStack:

```bash
# Install tflocal wrapper
pip install terraform-local

# Apply local infrastructure
cd infra/terraform-local
tflocal init
tflocal apply

# Check outputs
tflocal output
```

This is the closest simulation of what `terraform apply` does in production.

---

## Teardown and reseed

```bash
# Wipe all LocalStack state and reseed from scratch
docker compose stop localstack
docker volume rm $(docker volume ls -q | grep localstack)
docker compose up -d localstack
docker compose logs -f localstack   # watch seed run
```

---

## Inspecting resources

```bash
# S3
docker compose exec localstack awslocal s3 ls
docker compose exec localstack awslocal s3 ls s3://finance-app-us-east-1-storage

# SQS
docker compose exec localstack awslocal sqs list-queues
docker compose exec localstack awslocal sqs get-queue-attributes \
    --queue-url http://localstack:4566/000000000000/finance-app-default \
    --attribute-names All

# SNS
docker compose exec localstack awslocal sns list-topics
docker compose exec localstack awslocal sns list-subscriptions

# SES
docker compose exec localstack awslocal ses list-identities
docker compose exec localstack awslocal ses get-send-statistics

# Dead-letter queue (failed jobs land here)
docker compose exec localstack awslocal sqs receive-message \
    --queue-url http://localstack:4566/000000000000/finance-app-dead-letter \
    --max-number-of-messages 10
```

---

## Flutter note

Pre-signed S3 URLs inside Docker use `localstack:4566` as the host, which
won't resolve on a device or emulator. The seed script sets:

```
AWS_URL=http://localhost:4566/finance-app-us-east-1-storage
```

This ensures URLs served to Flutter use your machine's `localhost`,
which the emulator can reach via `10.0.2.2:4566` (Android) or
`127.0.0.1:4566` (iOS simulator).

For physical devices, use your machine's LAN IP:
```env
AWS_URL=http://192.168.x.x:4566/finance-app-us-east-1-storage
```

---

## CloudFront

CloudFront is only available in **LocalStack Pro**. In the Community edition
the seed script automatically falls back to direct S3 URLs. Your Laravel code
doesn't need to change — just `AWS_URL` differs:

| Environment | `AWS_URL` |
|---|---|
| Local (Community) | `http://localhost:4566/finance-app-us-east-1-storage` |
| Local (Pro) | `https://<localstack-cf-domain>` |
| Production | `https://<real-cloudfront-domain>` |