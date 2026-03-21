#!/bin/bash
# localstack-seed.sh
# Simulates the full production AWS stack on LocalStack.
# Run this once after `docker compose up` to bootstrap all resources.
#
# Usage:
#   Option A — inside the container (awslocal is pre-installed there):
#     docker compose exec localstack bash /etc/localstack/init/ready.d/localstack-seed.sh
#
#   Option B — from your host machine:
#     brew install awscli && pip3 install awscli-local
#     bash infra/scripts/localstack-seed.sh
#
# NOTE: awslocal is NOT installed on your host by default.
# It is a wrapper around the AWS CLI that points at http://localhost:4566.

set -eo pipefail

# Print the failing command on error instead of silently stopping
trap 'echo ""; echo "❌ Seed failed at line $LINENO — command: $BASH_COMMAND"; echo ""' ERR

LOCALSTACK_ENDPOINT="http://localhost:4566"
REGION="${AWS_DEFAULT_REGION:-us-east-1}"
# S3 bucket names must be lowercase — force it regardless of what APP_NAME contains
APP_NAME=$(echo "${APP_NAME:-finance-app}" | tr '[:upper:]' '[:lower:]' | tr '_' '-')
ACCOUNT_ID="000000000000"

# Shorthand — all awslocal commands use these
export AWS_ACCESS_KEY_ID=local
export AWS_SECRET_ACCESS_KEY=local
export AWS_DEFAULT_REGION=$REGION

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║   LocalStack — Simulating Production AWS Stack       ║"
echo "╚══════════════════════════════════════════════════════╝"
echo ""

###############################################################################
# S3
###############################################################################
echo "━━━ S3 ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

BUCKET_NAME="${APP_NAME}-${REGION}-storage"

echo "--> Creating S3 bucket: $BUCKET_NAME"
awslocal s3 mb s3://$BUCKET_NAME --region $REGION

echo "--> Enabling versioning (mirrors production)"
awslocal s3api put-bucket-versioning \
    --bucket $BUCKET_NAME \
    --versioning-configuration Status=Enabled

echo "--> Enabling server-side encryption (mirrors production)"
awslocal s3api put-bucket-encryption \
    --bucket $BUCKET_NAME \
    --server-side-encryption-configuration '{
        "Rules": [{
            "ApplyServerSideEncryptionByDefault": {
                "SSEAlgorithm": "AES256"
            }
        }]
    }'

echo "--> Creating bucket folders (uploads, avatars, exports)"
TMPFILE=$(mktemp)
echo "placeholder" > "$TMPFILE"
for folder in uploads avatars exports; do
    awslocal s3 cp "$TMPFILE" s3://$BUCKET_NAME/$folder/.keep
done
rm -f "$TMPFILE"

echo "✓ S3 bucket ready: $BUCKET_NAME"

###############################################################################
# SQS
###############################################################################
echo ""
echo "━━━ SQS ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

DLQ_NAME="${APP_NAME}-dead-letter"
QUEUE_NAME="${APP_NAME}-default"

echo "--> Creating dead-letter queue: $DLQ_NAME"
awslocal sqs create-queue --queue-name "$DLQ_NAME"
DLQ_URL="http://sqs.${REGION}.localhost.localstack.cloud:4566/${ACCOUNT_ID}/${DLQ_NAME}"
DLQ_ARN="arn:aws:sqs:${REGION}:${ACCOUNT_ID}:${DLQ_NAME}"

echo "--> Creating main queue: $QUEUE_NAME"
awslocal sqs create-queue --queue-name "$QUEUE_NAME"
QUEUE_URL="http://sqs.${REGION}.localhost.localstack.cloud:4566/${ACCOUNT_ID}/${QUEUE_NAME}"
QUEUE_ARN="arn:aws:sqs:${REGION}:${ACCOUNT_ID}:${QUEUE_NAME}"

echo "--> Setting queue attributes (visibility, retention, long polling, DLQ)"
awslocal sqs set-queue-attributes     --queue-url "$QUEUE_URL"     --attributes VisibilityTimeout=90
awslocal sqs set-queue-attributes     --queue-url "$QUEUE_URL"     --attributes MessageRetentionPeriod=86400
awslocal sqs set-queue-attributes     --queue-url "$QUEUE_URL"     --attributes ReceiveMessageWaitTimeSeconds=10
REDRIVE_FILE=$(mktemp)
python3 -c "import json,sys; print(json.dumps({'RedrivePolicy': json.dumps({'deadLetterTargetArn': sys.argv[1], 'maxReceiveCount': '3'})}))" "$DLQ_ARN" > "$REDRIVE_FILE"
awslocal sqs set-queue-attributes --queue-url "$QUEUE_URL" --attributes file://"$REDRIVE_FILE"
rm -f "$REDRIVE_FILE"

echo "✓ SQS queues ready:"
echo "   Main:        $QUEUE_URL"
echo "   Dead-letter: $DLQ_URL"

###############################################################################
# SNS
###############################################################################
echo ""
echo "━━━ SNS ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

TOPIC_NAME="${APP_NAME}-notifications"

echo "--> Creating SNS topic: $TOPIC_NAME"
TOPIC_ARN=$(awslocal sns create-topic \
    --name $TOPIC_NAME \
    --query 'TopicArn' --output text)

echo "--> Subscribing SQS queue to SNS topic (fan-out)"
awslocal sns subscribe \
    --topic-arn $TOPIC_ARN \
    --protocol sqs \
    --notification-endpoint $QUEUE_ARN \
    --output text

echo "✓ SNS topic ready: $TOPIC_ARN"

###############################################################################
# SES
###############################################################################
echo ""
echo "━━━ SES ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

SES_FROM="${SES_FROM_EMAIL:-noreply@finance-app.local}"
SES_DOMAIN="${SES_DOMAIN:-finance-app.local}"

echo "--> Verifying sender email: $SES_FROM"
awslocal ses verify-email-identity --email-address $SES_FROM

echo "--> Verifying sender domain: $SES_DOMAIN"
awslocal ses verify-domain-identity --domain $SES_DOMAIN

echo "✓ SES identities verified"

###############################################################################
# CloudFront (LocalStack Pro only — skipped in Community edition)
###############################################################################
echo ""
echo "━━━ CloudFront ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Check if CloudFront is available (requires LocalStack Pro)
CF_STATUS=$(awslocal cloudfront list-distributions \
    --query 'DistributionList.Items' \
    --output text 2>/dev/null && echo "available" || echo "unavailable")

if [ "$CF_STATUS" = "available" ]; then
    echo "--> Creating CloudFront distribution in front of S3..."
    DISTRIBUTION=$(awslocal cloudfront create-distribution \
        --distribution-config "{
            \"CallerReference\": \"$APP_NAME-$(date +%s)\",
            \"Comment\": \"$APP_NAME local CDN\",
            \"DefaultCacheBehavior\": {
                \"TargetOriginId\": \"S3-$BUCKET_NAME\",
                \"ViewerProtocolPolicy\": \"redirect-to-https\",
                \"ForwardedValues\": {
                    \"QueryString\": false,
                    \"Cookies\": {\"Forward\": \"none\"}
                },
                \"MinTTL\": 0
            },
            \"Origins\": {
                \"Quantity\": 1,
                \"Items\": [{
                    \"Id\": \"S3-$BUCKET_NAME\",
                    \"DomainName\": \"$BUCKET_NAME.s3.amazonaws.com\",
                    \"S3OriginConfig\": {\"OriginAccessIdentity\": \"\"}
                }]
            },
            \"Enabled\": true
        }")
    CF_DOMAIN=$(echo $DISTRIBUTION | python3 -c "import sys,json; print(json.load(sys.stdin)['Distribution']['DomainName'])" 2>/dev/null || echo "")
    echo "✓ CloudFront distribution: https://$CF_DOMAIN"
else
    echo "⚠ CloudFront not available in LocalStack Community."
    echo "  Falling back: use S3 direct URL for local asset serving."
    echo "  S3 URL: http://localhost:4566/$BUCKET_NAME"
    CF_DOMAIN="localhost:4566/$BUCKET_NAME"
fi

###############################################################################
# Print .env values
###############################################################################
echo ""
echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║   Add these to your .env (or .env.localstack)                        ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""
echo "AWS_ACCESS_KEY_ID=local"
echo "AWS_SECRET_ACCESS_KEY=local"
echo "AWS_DEFAULT_REGION=$REGION"
echo "AWS_ENDPOINT=http://localstack:4566"
echo "AWS_USE_PATH_STYLE_ENDPOINT=true"
echo ""
echo "AWS_BUCKET=$BUCKET_NAME"
echo "AWS_URL=http://localhost:4566/$BUCKET_NAME"
echo ""
echo "QUEUE_CONNECTION=sqs"
echo "SQS_PREFIX=http://localstack:4566/$ACCOUNT_ID"
echo "SQS_QUEUE=${APP_NAME}-default"
echo ""
echo "SNS_ARN_PREFIX=arn:aws:sns:$REGION:$ACCOUNT_ID:"
echo "SNS_TOPIC_ARN=$TOPIC_ARN"
echo ""
echo "MAIL_MAILER=ses-local"
echo "MAIL_FROM_ADDRESS=$SES_FROM"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅  LocalStack stack seeded successfully."
echo ""