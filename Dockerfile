FROM ubuntu:18.04

# Install dependencies
ENV DEBIAN_FRONTEND=noninteractive
RUN apt update -y && apt upgrade -yqq
RUN apt install -y curl nginx yaz libyaz-dev php php-fpm php-pear php-dev php-curl composer

# Install NodeJS
RUN apt remove --purge nodejs npm
RUN apt clean -y && apt autoclean -y
RUN apt install -f && apt autoremove -y
RUN curl -sL https://deb.nodesource.com/setup_10.x | bash -
RUN apt install -y nodejs

# Build YAZ extension
RUN pecl channel-update pecl.php.net
RUN printf "\n" | pecl install yaz
RUN echo 'extension=yaz.so' >> /etc/php/7.2/fpm/php.ini

# Build app
WORKDIR /rosetta
COPY . .
COPY ./.docker/nginx.conf /etc/nginx/sites-available/default
RUN npm update -g npm && npm install && npm run build
RUN composer install --no-dev --optimize-autoloader

# Start app
RUN systemctl restart php-fpm
RUN systemctl restart nginx
EXPOSE 80
