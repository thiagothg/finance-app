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