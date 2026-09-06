FROM php:8.4-cli-alpine

WORKDIR /app

# I had to install pgsql libraries
RUN apk add postgresql-dev libzip-dev unzip --no-cache

RUN docker-php-ext-install pdo pdo_pgsql zip

RUN curl https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . .

RUN composer install

EXPOSE 8088

CMD [ "php", "-S", "0.0.0.0:8088", "-t", "api/" ]
