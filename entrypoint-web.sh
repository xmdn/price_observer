#!/bin/sh

# Load environment variables
export $(grep -v '^#' .env | xargs)

echo "Checking if Horizon should be started..."

if [ "$HORIZON_ON" = "true" ] || [ "$HORIZON_ON" = "1" ]; then
    echo "Starting Laravel Horizon..."
    exec php artisan horizon &
fi

echo "Horizon check passed..."

# Keep the container running to prevent exit
# exec tail -f /dev/null