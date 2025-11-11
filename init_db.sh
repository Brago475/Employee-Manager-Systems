#!/bin/bash

# Database initialization script for Employee Manager System
# Usage: ./init_db.sh [mysql_user] [mysql_password]

set -e

DB_USER="${1:-root}"
DB_PASS="${2:-}"
DB_NAME="employee_dashboard_db"

if [ -z "$DB_PASS" ]; then
    echo "Creating database and importing schema..."
    mysql -u "$DB_USER" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -u "$DB_USER" "$DB_NAME" < database/schema.sql
    mysql -u "$DB_USER" "$DB_NAME" < database/seed.sql
    echo "Database initialized successfully!"
    echo ""
    echo "Sanity check:"
    mysql -u "$DB_USER" -e "USE $DB_NAME; SHOW TABLES; SELECT COUNT(*) AS employees FROM employees;"
else
    echo "Creating database and importing schema..."
    mysql -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/schema.sql
    mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < database/seed.sql
    echo "Database initialized successfully!"
    echo ""
    echo "Sanity check:"
    mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SHOW TABLES; SELECT COUNT(*) AS employees FROM employees;"
fi

