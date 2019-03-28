FROM php:7.3-fpm-stretch

# Install dependencies
RUN apt update -y && apt upgrade -yqq
RUN apt install -y curl gnupg

# Install NodeJS
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash -
RUN apt install -y nodejs

# Install Yarn
RUN curl -sL https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt update -y && apt install -y yarn

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
RUN yarn install && yarn build
RUN composer install --no-dev --optimize-autoloader
