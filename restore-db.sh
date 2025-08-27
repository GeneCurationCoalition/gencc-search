#!/bin/bash

# Script to restore gencc-search database from latest production backup
# Usage: ./restore-db.sh

set -e  # Exit on any error

echo "🔄 Starting database restore from production backup..."

# Step 1: Find the latest backup date
echo "📅 Finding latest backup date..."
LATEST_DATE=$(gsutil ls gs://web-prod-backups/ | grep -E '/[0-9]{8}/$' | sort | tail -1 | sed 's|gs://web-prod-backups/||' | sed 's|/||')

if [ -z "$LATEST_DATE" ]; then
    echo "❌ Error: No backup dates found in gs://web-prod-backups/"
    exit 1
fi

echo "✅ Latest backup date found: $LATEST_DATE"

# Step 2: Restore database from latest backup
echo "🗄️  Restoring database from backup..."
BACKUP_FILE="WB${LATEST_DATE}S_web2_genccv1.sql.gz"
BACKUP_PATH="gs://web-prod-backups/${LATEST_DATE}/${BACKUP_FILE}"

echo "📥 Downloading and restoring from: $BACKUP_PATH"
echo "⚠️  This will overwrite the current 'laravel' database!"

echo "🔄 Restoring database (this may take a few minutes)..."
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2)
gsutil cat "$BACKUP_PATH" | gunzip -c | mysql -u root -p"$DB_PASSWORD" laravel

echo "✅ Database restore completed"

# Step 3: Run migrations
echo "🔄 Checking migration status..."
php artisan migrate:status

echo "🔄 Running migrations..."
php artisan migrate --force

echo "✅ Migrations completed"

# Step 4: Configure allow posts setting
echo "🔄 Configuring API settings..."
php artisan gencc:allow-posts yes

echo "✅ API settings configured"

echo ""
echo "🎉 Database restore completed successfully!"
echo "📊 Summary:"
echo "   - Restored from: $BACKUP_PATH"
echo "   - Migrations: Applied"
echo "   - API Posts: Enabled"
echo "   - Token: eKtvHtwlWB1y7Q5MxcLwWyjYF55Ltvs0ITWlAi8UNAcXZkzZiYUi0v7HvMOr"
echo ""
echo "💡 You can now start the server with: php artisan serve"
