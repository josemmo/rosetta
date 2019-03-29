#!/bin/bash
while ! nc -z db 3306; do sleep 5; done
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:schema:update --force
