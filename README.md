# GPTs Chatbot

Chatbot app powered by Laravel + external booking API.

## Stack

- **Framework**: Laravel 11 (PHP 8.3)
- **Database**: MySQL / PostgreSQL
- **Cache/Queue**: Redis
- **Runtime**: FrankenPHP (via MonkeysCloud)

## Local Development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

## Deployment — MonkeysCloud

### 1. Chuẩn bị repo GitHub

Push code lên GitHub (repo phải public hoặc kết nối GitHub với MonkeysCloud).

### 2. Tạo project trên MonkeysCloud

1. Đăng ký tại [monkeys.cloud](https://monkeys.cloud) (GitHub hoặc Google — không cần thẻ)
2. Tạo **Project** mới
3. Thêm **Instance 1** — chọn stack **Laravel** (FrankenPHP, PHP 8.4)
4. Thêm **Database 1** — chọn **MySQL 8.4**
5. Thêm **Database 2** — chọn **Redis 7** (cho queue, cache, session)
6. Kết nối repo GitHub → push → auto-deploy

### 3. Cấu hình Environment Variables

Trong MonkeysCloud dashboard, thêm các biến môi trường:

```
APP_ENV=production
APP_DEBUG=false
APP_KEY=        # chạy php artisan key:generate để tạo
DB_CONNECTION=mysql
DB_HOST=<MySQL host từ MonkeysCloud>
DB_PORT=3306
DB_DATABASE=<tên database>
DB_USERNAME=<username>
DB_PASSWORD=<password>
REDIS_HOST=<Redis host từ MonkeysCloud>
REDIS_PORT=6379
REDIS_PASSWORD=null
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

### 4. Chạy migration

Sau khi deploy thành công, chạy:

```
php artisan migrate
```

### 5. Instance 2 — Queue Worker (tùy chọn)

Nếu app dùng queue jobs, thêm **Instance 2** chọn **Worker** và set:

```
COMMAND=php artisan queue:work redis --sleep=3 --tries=3
```

### Lưu ý

- Free instance **ngủ sau 30 phút** không active → wake-up vài giây
- App debug mode: **tắt** (`APP_DEBUG=false`) khi lên production
