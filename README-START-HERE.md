# Back Then Stories CMS V2

Đây là CMS Laravel thật, có đăng nhập admin và CRUD cho Articles / Artists / Categories / Media / Sites / Ad Slots.

## Cách dễ nhất trên Windows (local)
1. Cài **PHP 8.2+** và **Composer**. Dễ nhất là Laravel Herd, hoặc PHP + Composer riêng.
2. Giải nén project.
3. Double-click `SETUP_LOCAL_WINDOWS.bat`.
4. Khi script yêu cầu, nhập tên/email/password admin.
5. Double-click `RUN_LOCAL_WINDOWS.bat`.
6. Mở: `http://127.0.0.1:8000/admin`

## Nếu chạy lệnh thủ công
```bash
composer install
copy .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate --seed
php artisan storage:link
php artisan app:create-admin
php artisan serve
```

## Những gì đã có
- Admin login + session auth.
- Dashboard.
- Article workflow: Draft / Review / Scheduled / Published.
- Rich HTML editor đơn giản, không phụ thuộc plugin bên ngoài.
- Upload featured image; tự chuyển WebP nếu server có GD.
- Media library: upload, copy URL, delete.
- Artist / Category management.
- Multi-site database model + host resolver.
- SEO title, meta description, canonical, Open Graph, Article JSON-LD.
- sitemap.xml, robots.txt, ads.txt.
- GTM + GA4 fields.
- GAM-ready ad slot configuration: banner_top/mid/bot, anchor, interstitial, rewarded. Tất cả OFF mặc định.

## Production
Local mặc định dùng SQLite để cài dễ. Khi deploy production, nên chuyển `.env` sang MySQL, dùng HTTPS, Cloudflare, backups và cấu hình web root trỏ vào `/public`.

### MySQL example
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=back_then_stories
DB_USERNAME=YOUR_USER
DB_PASSWORD=YOUR_PASSWORD
```

## Monetization safety
Không dùng GAM Network ID, ad-unit path, ads.txt hay account ID của website khác. Chỉ bật monetization khi account/site của bạn được Google hoặc monetization partner phê duyệt.
