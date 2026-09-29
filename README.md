# GPS Server

Laravel 8 application for GPS tracking — integrates GPSWOX UI with a self-hosted Traccar server.

---

## Requirements

- PHP 8.0+
- MySQL / MariaDB 10.3+
- Nginx
- Redis
- Node.js 16+ (for the socket.io service)
- Composer
- Traccar

---

## Setup

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate --force

# Build caches
php artisan optimize:clear
The .env file must include both the Laravel database connection and a separate Traccar database connection.

Socket.io Service
Live updates are served by the Node.js service in socket/.

bash
cd socket
npm install
cd ..

pm2 start socket.config.js --name socket
pm2 save
Listens on port 9001.

Run Locally
bash
php artisan serve --port=8000
Open http://127.0.0.1:8000.

Ports
Port	Service
8080	Web interface
8082	Traccar UI
9001	Socket.io
6023	Traccar GT06
6027	Traccar Teltonika
Architecture
text
GPS Device → Traccar (port 6023/6027)
                ↓
         tc_positions (Traccar DB)
                ↓
         Laravel reads via traccar_device_id
                ↓
         Web UI (map, history, reports)
Laravel does not write positions. Traccar owns the data flow.

Common Commands
bash
php artisan optimize:clear       # clear caches
php artisan migrate --force      # run pending migrations
php artisan migrate:status       # check migration state
php artisan route:list           # list routes
Scheduled Tasks
Add to crontab:

 * * * cd /var/www/L8-Server-Project && php artisan schedule:run >> /dev/null 2>&1
Troubleshooting
Issue	Check
No live updates	pm2 list — socket.io must be online
Unknown column on insert	Run php artisan migrate --force
Device not showing	Verify devices.traccar_device_id maps to a valid tc_devices.id
Login loop	Check users.subscription_expiration
Address empty	Check geocoder network access
License
Built on a commercial GPSWOX base. Redistribution subject to the original vendor's terms.

