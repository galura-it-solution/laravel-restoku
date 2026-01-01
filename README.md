# Restoku API

Laravel API untuk Restoku (Restaurant Order Management).

## Kebutuhan Sistem
- PHP 8.1+
- Composer
- PostgreSQL
- Mail SMTP (untuk OTP)
- Object storage kompatibel S3 (MinIO/R2/S3) untuk gambar menu

## Cara Menjalankan Aplikasi
1) Salin env:
```
cp .env.example .env
```
2) Konfigurasikan database, mail, dan storage di `.env`.
3) Install dependensi:
```
composer install
```
4) Generate app key:
```
php artisan key:generate
```
5) Jalankan migrasi dan seed admin:
```
php artisan migrate --seed
```
6) Jalankan server:
```
php artisan serve
```

Default admin:
- email: `admin@restoku.test`
- password: `password`

## Database (PostgreSQL)
Contoh `.env`:
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=restoku
DB_USERNAME=postgres
DB_PASSWORD=secret
```

## Object Storage (MinIO / S3)
Contoh konfigurasi MinIO di `.env`:
```
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=restoku
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Buat bucket:
```
mc alias set local http://127.0.0.1:9000 minioadmin minioadmin
mc mb local/restoku
```

## Konfigurasi Harga
Service charge dan pajak (opsional):
```
RESTOKU_SERVICE_CHARGE_PERCENT=0
RESTOKU_TAX_PERCENT=0
```

## Alur Auth (OTP)
- Register: `POST /api/v1/auth/register`
- Login: `POST /api/v1/auth/login`
- Verify OTP: `POST /api/v1/auth/verify-otp`
- Logout: `POST /api/v1/auth/logout`

Rate limit:
- register/login: 5/min
- verify-otp: 10/min

## Fitur Utama
- Manajemen kategori, menu, meja, dan pesanan.
- Idempotent order via header `Idempotency-Key`.
- Status order: `pending -> processing -> done`.
- Upload gambar menu ke storage S3-compatible.
- Streaming update order/notification via SSE.
- Role staff untuk aksi administratif.

## Endpoint Utama
Semua endpoint di bawah perlu `Authorization: Bearer <token>`.

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

## Paket Penting dan Dampaknya
Runtime (production):
- `laravel/framework`: inti aplikasi; performa dipengaruhi oleh config cache dan route cache.
- `laravel/sanctum`: auth token; aman untuk API, perlu proteksi token dan HTTPS.
- `league/flysystem-aws-s3-v3`: akses storage S3; latency tergantung jaringan dan bucket.
- `guzzlehttp/guzzle`: HTTP client; bisa menambah latency bila dipakai sync di request.
- `laravel/tinker`: console interaktif; nonaktifkan di produksi untuk mengurangi risiko akses tidak perlu.

Dev/Test:
- `phpunit/phpunit`: test suite; tidak berdampak ke runtime.
- `mockery/mockery`: mocking; hanya saat test.
- `fakerphp/faker`: data dummy; hanya saat test/seed.
- `laravel/pint`: formatter; tidak berdampak ke runtime.
- `nunomaduro/collision`: output error di dev; tidak untuk production.
- `spatie/laravel-ignition`: debug page; pastikan `APP_DEBUG=false` di production.
- `laravel/sail`: dev Docker; tidak dipakai di runtime.

## Catatan
- URL gambar menu dikembalikan sebagai `image_url`.
- Order mendukung `note` dan item-level `notes`.

## Highlight Security & Performa
- Security: `laravel/sanctum` untuk token auth, rate limiting pada auth endpoint, validasi request di FormRequest, policy/role `staff`, SSE hanya untuk user terautentikasi.
- Performa: idempotency untuk mencegah order duplikat, index DB pada field lookup, pagination + polling, cache untuk nomor antrian, storage S3 untuk offload file statik.
- Alasan SSE: kebutuhan update bersifat satu arah (server -> client), lebih sederhana dari WebSocket, kompatibel dengan HTTP/proxy umum, dan lebih mudah dioperasikan tanpa infrastruktur socket khusus.
