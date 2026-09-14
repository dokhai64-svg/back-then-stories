# Back Then Stories — Online deployment

Recommended first deployment: Railway + managed MySQL (or PostgreSQL) + a persistent volume for uploaded media.

## App service
- Source: this project/repository
- Pre-deploy command: `chmod +x railway/init-app.sh && sh railway/init-app.sh`
- Health endpoint: `/up`
- Public domain: generate a Railway domain first; add a custom domain later.

## Required variables
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY=<generated Laravel key>`
- `APP_URL=https://<public-domain>`
- `DB_CONNECTION=mysql` with `DB_URL` pointing to the Railway MySQL service, or `pgsql` for PostgreSQL
- `SESSION_DRIVER=file`
- `CACHE_STORE=file`
- `QUEUE_CONNECTION=sync`
- `FILESYSTEM_DISK=public`

## First admin
Set secure hosting variables:
- `ADMIN_NAME=Administrator`
- `ADMIN_EMAIL=<your admin email>`
- `ADMIN_PASSWORD=<strong random password>`

The pre-deploy command creates/updates that admin automatically.

## Uploaded images
Mount persistent storage at the app's `storage/app/public` directory, or replace the public filesystem disk with S3-compatible object storage before production scale.

## Monetization
Keep GAM/ad slots disabled until the site is approved by your own monetization account/partner. Never reuse another publisher's network code or ad-unit IDs.
