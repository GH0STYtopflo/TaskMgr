FROM php:latest

WORKDIR /app
COPY . .

RUN apt update
RUN apt install composer -y
RUN docker-php-ext-install pdo pdo_pgsql

RUN composer install

EXPOSE 8088

CMD [ "php", "-S", "0.0.0.0:8088", "-t", "api/" ]