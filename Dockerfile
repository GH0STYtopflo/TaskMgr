FROM php:8.4

WORKDIR /app

# I had to install pgsql libraries
RUN apt-get update && apt-get install -y libpq-dev unzip libzip-dev && rm -rf /var/lib/apt/lists/*

COPY . .

RUN docker-php-ext-install pdo pdo_pgsql zip

RUN curl https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install

EXPOSE 8088

CMD [ "php", "-S", "0.0.0.0:8088", "-t", "api/" ]
