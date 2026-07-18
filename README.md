# CRISIMAP

CRISIMAP is a GIS-based monitoring and mapping system for AICS beneficiary distribution for the Municipal Social Welfare and Development Office of Tagudin, Ilocos Sur.

## Features
- Role-based access for MSWDO Head, AICS Focal Person, Social Welfare Officer, and Technical Expert
- Beneficiary CRUD with geocoding support
- Interactive Leaflet map with OSM tiles
- Dashboard analytics and recent activity
- PDF and Excel report exports
- Settings for barangays and assistance types
- Digital case-record logbook with household members, housing details, signed-record uploads, and linked assistance beneficiaries

## Setup
1. Install PHP dependencies:
   - composer install
2. Copy `.env.example` to `.env` and configure your database.
3. Generate an app key:
   - php artisan key:generate
4. Create the SQLite database file:
   - mkdir -p database
   - touch database/database.sqlite
5. Run migrations and seed data:
   - php artisan migrate --seed
6. Create the storage link:
   - php artisan storage:link
7. Start the app:
   - php artisan serve

## Demo Accounts
- admin@crisimap.test / password123
- focal@crisimap.test / password123
- officer@crisimap.test / password123

## Production Notes
- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Run `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`
