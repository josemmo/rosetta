#!/bin/bash
# Wait for database container to boot up
while ! nc -z db 3306; do sleep 5; done

# Initialize database
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:update --force

# Start FPM socket
docker-php-entrypoint php-fpm
