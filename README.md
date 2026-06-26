# AffanPay Laravel Guide

Panduan Laravel ini disediakan untuk pengguna payment gateway AffanPay yang mahu membina aliran pembayaran lengkap menggunakan Laravel.

Projek ini ialah demo rujukan yang menunjukkan bagaimana untuk:

- bina produk dan order
- cipta bill di AffanPay
- redirect pelanggan ke halaman pembayaran AffanPay
- terima webhook pembayaran
- semak semula status bayaran secara selamat
- paparkan status bayaran secara automatik tanpa refresh manual
- harden aplikasi sebelum diterbitkan ke GitHub atau production

## Objektif Projek

Repositori ini bertindak sebagai starter guide dan contoh integrasi untuk:

- `Laravel + AffanPay bill creation`
- `return URL + webhook verification`
- `payment status tracking`
- `basic admin configuration`
- `security hardening`

Ia sesuai dijadikan rujukan oleh:

- pembangun Laravel yang baru hendak integrasi AffanPay
- merchant atau team teknikal yang mahu faham flow pembayaran
- pasukan yang mahu publish kod contoh ke GitHub dengan amalan keselamatan yang lebih baik

## Teknologi Digunakan

- PHP
- Laravel
- SQLite untuk demo
- Tailwind CSS melalui CDN
- AffanPay API

## Apa Yang Sudah Dibuat

Antara fungsi utama yang sudah tersedia dalam projek ini:

- senarai produk dan paparan produk
- borang pembelian dan penciptaan order
- penciptaan bill AffanPay melalui API
- redirect pelanggan ke URL bayaran AffanPay
- simpan `bill reference` dan `payment reference`
- webhook endpoint untuk update status bayaran
- semakan status bill melalui API AffanPay
- auto polling status pada halaman order
- animasi live transfer `A -> B` semasa pembayaran sedang diproses
- admin panel untuk simpan credential sandbox dan live
- webhook alias di:
  - `POST /api/v1/payments/webhook`
  - `POST /webhook/affanpay`

## Payment Flow

Flow pembayaran dalam projek ini adalah seperti berikut:

1. Pelanggan pilih produk.
2. Pelanggan isi maklumat pembelian.
3. Sistem cipta `order` dan `payment` dalam database.
4. Sistem panggil API AffanPay untuk `create bill`.
5. AffanPay pulangkan:
   - `bill id`
   - `payment URL`
6. Pelanggan di-redirect ke halaman pembayaran AffanPay.
7. Selepas pelanggan bayar:
   - AffanPay redirect pelanggan balik ke halaman order
   - AffanPay hantar webhook ke endpoint aplikasi
8. Aplikasi verify status semula melalui API AffanPay.
9. Status order dikemas kini ke:
   - `paid`
   - `processing`
   - `failed`
10. Halaman order auto-refresh status secara berkala tanpa perlu klik manual.

## Return, Webhook Dan Status Verification

Dalam implementasi ini:

- `return URL` tidak dianggap sebagai sumber kebenaran utama
- `webhook` ialah sumber utama untuk update status asynchronous
- `requery/status check` digunakan sebagai verification tambahan

Ini penting kerana redirect pelanggan sahaja tidak cukup untuk membuktikan bayaran benar-benar berjaya.

Pendekatan yang digunakan sekarang:

- return page hanya menjadi signal bahawa pelanggan telah pulang
- aplikasi akan panggil status API AffanPay untuk semak status sebenar
- webhook akan update status order apabila callback diterima

## Rujukan Pembayaran

Projek ini membezakan dua rujukan utama:

- `bill reference`
  - contoh: ID bill yang dikeluarkan semasa create bill
- `payment reference`
  - contoh: rujukan transaksi bayaran sebenar daripada AffanPay

Keutamaan semakan status:

1. `payment_reference`
2. `bill_reference`
3. `external_ref` sebagai fallback dalaman

## Endpoint Penting

### Public

- `GET /`
- `GET /products`
- `GET /products/{product}`
- `GET /orders/{public_token}`
- `GET /orders/{public_token}/status`
- `POST /orders/{public_token}/retry-payment`
- `POST /orders/{public_token}/check-status`

### Admin

- `GET /admin`
- `POST /admin/switch-environment`
- `POST /admin/save-credentials`

### Webhook

- `GET /api/v1/payments/webhook`
- `POST /api/v1/payments/webhook`
- `POST /webhook/affanpay`

## Keselamatan Yang Sudah Diperketatkan

Sebelum publish ke GitHub, beberapa hardening telah dibuat berdasarkan amalan Laravel, PSR, OWASP dan prinsip cybersecurity umum.

### 1. Webhook security

- webhook dikecualikan daripada CSRF kerana ia dipanggil oleh pihak ketiga
- sebagai ganti, webhook kini memerlukan `shared secret`
- secret boleh dihantar melalui:
  - query param `token`
  - header `X-AffanPay-Webhook-Secret`
  - bearer token
- webhook request akan ditolak jika secret tidak sah dalam environment bukan local/testing

### 2. Admin protection

- route `/admin` dilindungi dengan Basic Auth
- admin turut dikenakan rate limit

### 3. Secret handling

- password credential AffanPay disimpan secara encrypted dalam table `settings`
- admin UI tidak lagi memaparkan password tersimpan
- pengguna hanya memasukkan password baru jika mahu rotate secret

### 4. Secure defaults

Dalam `.env.example`:

- `APP_DEBUG=false`
- `LOG_LEVEL=warning`
- `SESSION_ENCRYPT=true`
- `APP_KEY` tidak diletakkan dalam repo

### 5. Logging hardening

- log tidak lagi menyimpan full credential response
- log tidak lagi mendedahkan password
- log webhook dipermudahkan supaya kurang kebocoran data sensitif

### 6. Public order URL hardening

- akses order pelanggan tidak lagi menggunakan numeric ID yang mudah diteka
- aplikasi kini menggunakan `public_token` berbentuk UUID
- URL lama seperti `/orders/24` tidak lagi membuka order

### 7. Security headers

Header tambahan telah diperkenalkan:

- `X-Frame-Options`
- `X-Content-Type-Options`
- `Referrer-Policy`
- `Permissions-Policy`
- `Strict-Transport-Security` apabila request melalui HTTPS

### 8. Rate limiting

Rate limit digunakan pada:

- admin routes
- webhook routes
- live order status polling endpoint

## Setup Tempatan

### 1. Clone repositori

```bash
git clone <repo-url>
cd affanpay-laravel
```

### 2. Pasang dependency

```bash
composer install
```

### 3. Sediakan environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Semak nilai penting dalam `.env`

Contoh minimum:

```env
APP_NAME=AffanPay
APP_ENV=local
APP_DEBUG=false
APP_URL=http://localhost:8001

LOG_LEVEL=warning
SESSION_ENCRYPT=true

AFFANPAY_WEBHOOK_SECRET=change-this-secret
ADMIN_USERNAME=admin
ADMIN_PASSWORD=change-this-password
```

Nota:

- pastikan `APP_URL` sama dengan URL sebenar aplikasi anda
- jika local server berjalan di `localhost:8001`, tetapkan `APP_URL=http://localhost:8001`

### 5. Sediakan database

```bash
touch database/database.sqlite
php artisan migrate
php artisan db:seed
```

### 6. Jalankan aplikasi

```bash
php artisan serve --port=8001
```

## Konfigurasi AffanPay

Credential AffanPay boleh disimpan melalui halaman admin:

- sandbox email
- sandbox password
- live email
- live password

Aplikasi akan:

- authenticate ke AffanPay melalui token API
- create bill menggunakan `/api/v1/bill`
- check bill status menggunakan endpoint bill status

## Callback URL Dan Redirect URL

Semasa create bill, aplikasi akan menghantar:

- `redirect_url`
- `callback_url`
- `external_ref`

Pastikan URL production anda sah dan boleh diakses oleh AffanPay.

Contoh callback yang digunakan:

- `/api/v1/payments/webhook`

Jika anda gunakan shared secret webhook, callback sebenar akan membawa token tersebut melalui URL query.

## Admin Access

Admin dikunci menggunakan Basic Auth.

Tetapkan dalam `.env`:

```env
ADMIN_USERNAME=admin
ADMIN_PASSWORD=strong-password
```

Kemudian akses:

- `/admin`

Browser akan meminta username dan password.

## Ujian

Beberapa ujian keselamatan telah ditambah, termasuk:

- order public route menggunakan token rawak
- webhook perlu secret yang sah
- admin area memerlukan Basic Auth

Jalankan:

```bash
php artisan test --filter=SecurityHardeningTest
```

Untuk jalankan semua test:

```bash
php artisan test
```

## Checklist Sebelum Publish Ke GitHub

Sebelum push ke GitHub:

- jangan commit fail `.env`
- jangan commit `APP_KEY` production
- rotate mana-mana credential yang pernah terdedah
- pastikan `APP_DEBUG=false`
- pastikan `LOG_LEVEL=warning` atau lebih ketat
- pastikan `AFFANPAY_WEBHOOK_SECRET` telah ditetapkan
- pastikan `ADMIN_USERNAME` dan `ADMIN_PASSWORD` telah ditetapkan
- pastikan `APP_URL` menunjuk ke domain sebenar
- kosongkan log lama jika perlu

Cadangan:

```bash
php artisan optimize:clear
rm -f storage/logs/*.log
```

## Checklist Sebelum Production

- guna HTTPS
- guna domain sebenar untuk `APP_URL`
- semak bahawa webhook boleh diakses dari internet
- whitelist infrastructure yang sesuai jika perlu
- monitor log error tanpa menyimpan data sensitif
- semak semula rate limit dan timeout mengikut trafik sebenar

## Limitasi Semasa

Walaupun aplikasi ini sudah jauh lebih selamat, ia masih merupakan demo guide.

Perkara yang boleh dipertingkatkan lagi:

- signed URL yang mempunyai tempoh luput
- webhook signature verification rasmi jika AffanPay menyokong HMAC/signature
- CSP header yang lebih ketat
- audit logging berstruktur
- role-based admin access menggantikan Basic Auth
- secret management menggunakan vault atau platform secret manager

## Fail Rujukan Penting

- `app/Services/AffanPayService.php`
- `app/Http/Controllers/OrderController.php`
- `app/Http/Controllers/WebhookController.php`
- `app/Http/Controllers/AdminController.php`
- `app/Models/Order.php`
- `app/Models/Setting.php`
- `routes/web.php`
- `.env.example`
- `tests/Feature/SecurityHardeningTest.php`

## Ringkasan

Repositori ini ialah panduan Laravel untuk pengguna payment gateway AffanPay yang mahu memahami integrasi pembayaran end-to-end dengan:

- create bill
- redirect pelanggan
- webhook callback
- requery status
- auto tracking UI
- hardening keselamatan sebelum publish

Jika anda mahu gunakan repositori ini sebagai asas production, disarankan untuk teruskan dengan:

- signed URL
- production HTTPS enforcement
- secret rotation policy
- deployment checklist
- security review akhir sebelum go-live
