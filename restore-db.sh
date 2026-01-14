#!/bin/bash

# Script to restore gencc-search database from local backup
# Usage: ./restore-db.sh

set -e

BACKUP_FILE="database/backups/gencc-search-backup-20260103.sql.gz"

echo "🔄 Starting database restore from local backup..."

# Check if backup file exists
if [[ ! -f "$BACKUP_FILE" ]]; then
    echo "❌ Error: Backup file not found at $BACKUP_FILE"
    exit 1
fi

echo "📁 Using backup file: $BACKUP_FILE"

# Get database configuration from .env
DB_HOST=$(grep "^DB_HOST=" .env | cut -d '=' -f2)
DB_PORT=$(grep "^DB_PORT=" .env | cut -d '=' -f2)
DB_DATABASE=$(grep "^DB_DATABASE=" .env | cut -d '=' -f2)
DB_USERNAME=$(grep "^DB_USERNAME=" .env | cut -d '=' -f2)
DB_PASSWORD=$(grep "^DB_PASSWORD=" .env | cut -d '=' -f2)

echo "⚠️  This will overwrite the database: $DB_DATABASE"

echo "🔄 Restoring database (this may take a few minutes)..."
gunzip -c "$BACKUP_FILE" | mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE"

echo "✅ Database restore completed"

# Run migrations
echo "🔄 Running migrations..."
php artisan migrate --force

echo "✅ Migrations completed"

# Configure allow posts setting
echo "🔄 Configuring API settings..."
php artisan gencc:allow-posts yes

echo "✅ API settings configured"

echo ""
echo "🎉 Database restore completed successfully!"
echo "📊 Summary:"
echo "   - Restored from: $BACKUP_FILE"
echo "   - Migrations: Applied"
echo "   - API Posts: Enabled"
echo ""
echo "💡 You can now start the server with: php artisan serve"
