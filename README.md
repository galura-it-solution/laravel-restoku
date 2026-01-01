# Restoku API

Laravel API for Restoku (Restaurant Order Management).

## Requirements
- PHP 8.1+
- Composer
- PostgreSQL
- Mail SMTP (for OTP)
- S3-compatible storage (MinIO/R2/S3) for menu images

## Setup
1) Copy env file:
```
cp .env.example .env
```
2) Configure database, mail, and storage settings in `.env`.
3) Install dependencies:
```
composer install
```
4) Generate app key:
```
php artisan key:generate
```
5) Run migrations and seed default admin:
```
php artisan migrate --seed
```

Default admin user:
- email: `admin@restoku.test`
- password: `password`

## Database (PostgreSQL)
Example `.env` values:
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=restoku
DB_USERNAME=postgres
DB_PASSWORD=secret
```

## Object Storage (MinIO / S3)
Example MinIO config in `.env`:
```
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=restoku
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Create bucket:
```
mc alias set local http://127.0.0.1:9000 minioadmin minioadmin
mc mb local/restoku
```

## Pricing Configuration
Optional service charge and tax:
```
RESTOKU_SERVICE_CHARGE_PERCENT=0
RESTOKU_TAX_PERCENT=0
```

## Auth Flow (OTP)
- Register: `POST /api/v1/auth/register`
- Login: `POST /api/v1/auth/login`
- Verify OTP: `POST /api/v1/auth/verify-otp`
- Logout: `POST /api/v1/auth/logout`

Rate limits:
- register/login: 5/min
- verify-otp: 10/min

## Core Endpoints
All endpoints below require `Authorization: Bearer <token>`.

Customer + Admin:
- `GET /api/v1/categories`
- `GET /api/v1/menus`
- `GET /api/v1/tables`
- `POST /api/v1/orders`
- `GET /api/v1/orders`
- `GET /api/v1/orders/{order}`
- `GET /api/v1/notifications/stream`

Admin/Staff only:
- `POST /api/v1/categories`
- `PATCH /api/v1/categories/{category}`
- `DELETE /api/v1/categories/{category}`
- `POST /api/v1/menus`
- `PATCH /api/v1/menus/{menu}`
- `DELETE /api/v1/menus/{menu}`
- `POST /api/v1/menus/{menu}/image`
- `POST /api/v1/tables`
- `PATCH /api/v1/tables/{table}`
- `DELETE /api/v1/tables/{table}`
- `PATCH /api/v1/orders/{order}/assign`
- `PATCH /api/v1/orders/{order}/status`

## Notes
- Order creation is idempotent via `Idempotency-Key` header.
- Order status transitions: `pending -> processing -> done`.
- Menu image URL is returned as `image_url`.
- Orders support `note` and item-level `notes`.
