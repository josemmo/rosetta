FROM ubuntu:xenial

# Install dependencies
RUN apt update -y && apt upgrade -yqq
RUN apt install -y apache2 nodejs php yaz libyaz-dev

# Build YAZ extension
RUN pecl channel-update pecl.php.net
RUN printf "\n" | pecl install yaz

# Build app
WORKDIR /usr/src/app
COPY . .
RUN npm update -g npm && npm install && npm run build
RUN composer self-update && composer install --no-dev --optimize-autoloader
RUN composer require symfony/apache-pack --no-interaction

# Start app
EXPOSE 80
