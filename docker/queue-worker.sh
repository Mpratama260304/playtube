#!/bin/sh
# Queue worker wrapper script for dynamic queue connection
# Usage: queue-worker.sh <queue_name>

QUEUE_NAME="${1:-default}"
CONNECTION="${QUEUE_CONNECTION:-database}"

echo "Queue worker for queue=${QUEUE_NAME} on connection=${CONNECTION} - waiting for app readiness..."

# Wait for migrations to complete (max 60 seconds)
MAX_WAIT=60
WAITED=0
while [ $WAITED -lt $MAX_WAIT ]; do
    # Try a simple database query to check if cache table exists
    if php /var/www/html/artisan tinker --execute="DB::table('cache')->limit(1)->get();" > /dev/null 2>&1; then
        echo "Database ready, starting queue worker..."
        break
    fi
    echo "Waiting for database... (${WAITED}s/${MAX_WAIT}s)"
    sleep 5
    WAITED=$((WAITED + 5))
done

if [ $WAITED -ge $MAX_WAIT ]; then
    echo "WARNING: Database may not be ready, starting queue worker anyway..."
fi

echo "Starting queue worker for queue=${QUEUE_NAME} on connection=${CONNECTION}"

exec php /var/www/html/artisan queue:work "${CONNECTION}" \
    --queue="${QUEUE_NAME}" \
    --sleep=3 \
    --tries=3 \
    --max-time=3600 \
    --timeout=1800 \
    --memory=256 \
    --max-jobs=100
