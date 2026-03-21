# S3 (Simple Storage Service) — LocalStack + Laravel

Amazon S3 is an object storage service used to store and retrieve any amount of data — files, images, videos, backups, exports, and more. In a Laravel + Flutter app it's commonly used to store user-uploaded files and serve them via pre-signed URLs or public links.

LocalStack simulates S3 fully locally, so you can develop file upload/download flows without touching a real AWS bucket.

---

## What S3 is used for

- Storing user profile pictures, documents, media files
- Serving assets to the Flutter app via URL
- Generating pre-signed URLs for direct Flutter → S3 uploads
- Storing exports, reports, backups

---

## 1. Install the adapter

```bash
docker compose exec app composer require league/flysystem-aws-s3-v3 "^3.0"
```

---

## 2. Environment variables (`.env`)

```env
AWS_ACCESS_KEY_ID=local
AWS_SECRET_ACCESS_KEY=local
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_ENDPOINT=http://localstack:4566
AWS_USE_PATH_STYLE_ENDPOINT=true
```

---

## 3. Configure `config/filesystems.php`

```php
's3' => [
    'driver'                  => 's3',
    'key'                     => env('AWS_ACCESS_KEY_ID'),
    'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
    'region'                  => env('AWS_DEFAULT_REGION', 'us-east-1'),
    'bucket'                  => env('AWS_BUCKET'),
    'url'                     => env('AWS_URL'),
    'endpoint'                => env('AWS_ENDPOINT'),
    'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
    'throw'                   => true,   // surface real errors during development
],
```

---

## 4. Create the bucket

```bash
docker compose exec localstack awslocal s3 mb s3://your-bucket-name
docker compose exec localstack awslocal s3api put-bucket-acl \
    --bucket your-bucket-name \
    --acl public-read
```

---

## 5. Test from tinker

```bash
docker compose exec app php artisan config:clear
docker compose exec app php artisan tinker
```

```php
// Upload a file
Storage::disk('s3')->put('test.txt', 'Hello LocalStack');

// Check it exists
Storage::disk('s3')->exists('test.txt');   // true

// Get the URL
Storage::disk('s3')->url('test.txt');

// Generate a pre-signed URL (e.g. for Flutter to download directly)
Storage::disk('s3')->temporaryUrl('test.txt', now()->addMinutes(30));

// Delete
Storage::disk('s3')->delete('test.txt');
```

---

## 6. Verify on LocalStack side

```bash
# List all buckets
docker compose exec localstack awslocal s3 ls

# List contents of your bucket
docker compose exec localstack awslocal s3 ls s3://your-bucket-name

# Download a file to inspect it
docker compose exec localstack awslocal s3 cp s3://your-bucket-name/test.txt /tmp/test.txt
```

---

## Flutter note

Pre-signed URLs returned from LocalStack contain `localstack:4566` as the host, which won't resolve on a physical device or emulator. Use your machine's local IP instead:

```env
# For pre-signed URLs consumed by Flutter
AWS_URL=http://192.168.x.x:4566/your-bucket-name
```

Or proxy the download through a Laravel route that reads from S3 and streams back to the client.

---

## Common errors

| Error | Cause | Fix |
|-------|-------|-----|
| `PortableVisibilityConverter not found` | Missing adapter package | `composer require league/flysystem-aws-s3-v3 "^3.0"` |
| `put()` returns `false` silently | `throw` is `false`, endpoint wrong | Set `'throw' => true`, verify endpoint is `http://localstack:4566` |
| `Unable to check file existence` | SDK hitting real AWS | Endpoint is `127.0.0.1` instead of `localstack` |
| Bucket not found | Bucket not created yet | Run `awslocal s3 mb s3://your-bucket-name` |