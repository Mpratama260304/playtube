#!/bin/sh
# Queue worker wrapper script for dynamic queue connection
# Usage: queue-worker.sh <queue_name>

QUEUE_NAME="${1:-default}"
CONNECTION="${QUEUE_CONNECTION:-database}"

echo "Starting queue worker for queue=${QUEUE_NAME} on connection=${CONNECTION}"

exec php /var/www/html/artisan queue:work "${CONNECTION}" \
    --queue="${QUEUE_NAME}" \
    --sleep=3 \
    --tries=3 \
    --max-time=3600 \
    --timeout=1800 \
    --memory=256 \
    --max-jobs=100
