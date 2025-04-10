#!/bin/sh

if [ ! -d "vendor" ]; then
    echo "Installing PHP dependencies..."
    composer install
fi

if [ ! -d "node_modules" ]; then
    echo "Installing Node.js dependencies..."
    npm install
    npm run build
    mv node_modules public
fi

if ! php artisan migrate:status | grep -q "Ran?"; then
    echo "Running migrations..."
    php artisan migrate
    php artisan db:seed --class=AllDataSeeder
fi

echo "Container setup completed. Running application..."
exec "$@"
