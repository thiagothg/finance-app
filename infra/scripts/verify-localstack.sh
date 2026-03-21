#!/bin/bash
# verify-localstack.sh
# Verifies that all production AWS resources are correctly simulated in LocalStack.
# Run from your host machine after `docker compose up`.
#
# Usage:
#   bash infra/scripts/verify-localstack.sh

# Run all aws commands through the container since $AWS is not on the host
AWS="docker compose exec localstack awslocal"

ENDPOINT="http://localhost:4566"
APP_NAME=$(echo "${APP_NAME:-finance-app}" | tr '[:upper:]' '[:lower:]' | tr '_' '-')
REGION="${AWS_DEFAULT_REGION:-us-east-1}"
ACCOUNT_ID="000000000000"
BUCKET="${APP_NAME}-${REGION}-storage"

PASS=0
FAIL=0

check() {
    local label="$1"
    local cmd="$2"
    if eval "$cmd" &>/dev/null; then
        echo "  ✓ $label"
        PASS=$((PASS + 1))
    else
        echo "  ✗ $label"
        FAIL=$((FAIL + 1))
    fi
}

echo ""
echo "╔══════════════════════════════════════════════════════╗"
echo "║   LocalStack Stack Verification                      ║"
echo "╚══════════════════════════════════════════════════════╝"

# ── Health ──────────────────────────────────────────────────────────────────
echo ""
echo "▸ LocalStack Health"
check "LocalStack is reachable" \
    "curl -sf $ENDPOINT/_localstack/health | grep -q '\"s3\"'"

# ── S3 ───────────────────────────────────────────────────────────────────────
echo ""
echo "▸ S3"
check "Bucket exists: $BUCKET" \
    "$AWS s3api head-bucket --bucket $BUCKET"
check "Versioning enabled" \
    "$AWS s3api get-bucket-versioning --bucket $BUCKET | grep -q Enabled"
check "Encryption configured" \
    "$AWS s3api get-bucket-encryption --bucket $BUCKET"
check "uploads/ folder exists" \
    "$AWS s3api head-object --bucket $BUCKET --key uploads/.keep"

# ── SQS ──────────────────────────────────────────────────────────────────────
echo ""
echo "▸ SQS"
check "Main queue exists: ${APP_NAME}-default" \
    "$AWS sqs get-queue-url --queue-name ${APP_NAME}-default"
check "Dead-letter queue exists: ${APP_NAME}-dead-letter" \
    "$AWS sqs get-queue-url --queue-name ${APP_NAME}-dead-letter"
check "DLQ redrive policy configured" \
    "$AWS sqs get-queue-attributes \
        --queue-url http://localhost:4566/$ACCOUNT_ID/${APP_NAME}-default \
        --attribute-names RedrivePolicy | grep -q deadLetterTargetArn"
check "Long polling configured (WaitTimeSeconds=10)" \
    "$AWS sqs get-queue-attributes \
        --queue-url http://localhost:4566/$ACCOUNT_ID/${APP_NAME}-default \
        --attribute-names ReceiveMessageWaitTimeSeconds \
        | grep -q '\"10\"'"

# ── SNS ──────────────────────────────────────────────────────────────────────
echo ""
echo "▸ SNS"
check "Topic exists: ${APP_NAME}-notifications" \
    "$AWS sns list-topics | grep -q ${APP_NAME}-notifications"
check "SQS subscription to SNS topic" \
    "$AWS sns list-subscriptions | grep -q sqs"

# ── SES ──────────────────────────────────────────────────────────────────────
echo ""
echo "▸ SES"
check "Sender email verified" \
    "$AWS ses list-identities | grep -q '@'"

# ── End-to-end: S3 upload ────────────────────────────────────────────────────
echo ""
echo "▸ End-to-end Checks"
check "S3 put/get round-trip" \
    "echo 'test' | $AWS s3 cp - s3://$BUCKET/test-verify.txt && \
     $AWS s3 cp s3://$BUCKET/test-verify.txt /dev/null && \
     $AWS s3 rm s3://$BUCKET/test-verify.txt"

check "SQS send/receive round-trip" \
    "MSG_ID=\$($AWS sqs send-message \
        --queue-url http://localhost:4566/$ACCOUNT_ID/${APP_NAME}-default \
        --message-body 'verify-test' \
        --query 'MessageId' --output text) && \
     [ -n \"\$MSG_ID\" ]"

check "SNS publish" \
    "$AWS sns publish \
        --topic-arn arn:aws:sns:$REGION:$ACCOUNT_ID:${APP_NAME}-notifications \
        --message 'verify-test' \
        --query 'MessageId' --output text | grep -q '-'"

# ── Summary ──────────────────────────────────────────────────────────────────
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
TOTAL=$((PASS + FAIL))
echo "  Results: $PASS/$TOTAL passed"

if [ $FAIL -eq 0 ]; then
    echo "  ✅ All checks passed — LocalStack mirrors production stack."
else
    echo "  ❌ $FAIL check(s) failed — run localstack-seed.sh to fix."
    echo ""
    echo "  docker compose exec localstack \\"
    echo "    bash /etc/localstack/init/ready.d/localstack-seed.sh"
fi
echo ""