# Deployment Guide

## Shared Hosting or VPS
1. Upload the project files to your web root.
2. Ensure PHP 8.2+, Composer, MySQL, and Node.js are installed.
3. Set the document root to the `public` folder.
4. Configure `.env` for production:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `DB_CONNECTION=mysql`
   - `DB_HOST=...`
   - `DB_DATABASE=...`
   - `DB_USERNAME=...`
   - `DB_PASSWORD=...`
5. Run:
   - composer install --optimize-autoloader --no-dev
   - php artisan migrate --seed
   - php artisan config:cache
   - php artisan route:cache
   - php artisan view:cache
   - php artisan storage:link
6. Enable SSL and set the site to HTTPS.
