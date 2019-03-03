FROM php:7.3-fpm-stretch

# Install dependencies
RUN apt update -y && apt upgrade -yqq
RUN apt install -y curl gnupg

# Install nginx server
RUN apt install -y nginx
COPY ./.docker/nginx.conf /etc/nginx/sites-available/default

# Install NodeJS
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash -
RUN apt install -y nodejs

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

# Build ZIP extension
RUN apt install -y zip libzip-dev
RUN docker-php-ext-configure zip --with-libzip
RUN docker-php-ext-install zip

# Build YAZ extension
RUN apt install -y yaz libyaz-dev
RUN pecl install yaz
RUN docker-php-ext-enable yaz

# Build app
WORKDIR /rosetta
COPY . .
ENV APP_ENV=prod
RUN npm update -g npm && npm install && npm run build
RUN composer install --no-dev --optimize-autoloader

# Start app
CMD service nginx start
EXPOSE 80
