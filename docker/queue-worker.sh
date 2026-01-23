#!/bin/sh
# Queue worker wrapper script
# Usage: queue-worker.sh <queue_name>

QUEUE_NAME="${1:-default}"
CONNECTION="${QUEUE_CONNECTION:-database}"

echo "[Queue] Starting worker for queue=${QUEUE_NAME} on connection=${CONNECTION}"

# Wait for PHP-FPM and database to be ready
sleep 10

# Start the queue worker
exec php /var/www/html/artisan queue:work "${CONNECTION}" \
    --queue="${QUEUE_NAME}" \
    --sleep=3 \
    --tries=3 \
    --max-time=3600 \
    --timeout=1800 \
    --memory=256
