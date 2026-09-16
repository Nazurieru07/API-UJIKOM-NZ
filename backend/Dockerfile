# 1. Gunakan image dasar PHP versi CLI
FROM php:8.4-cli

# 2. Instal dependensi sistem dasar dan ekstensi PHP
RUN apt-get update && apt-get install -y libzip-dev zip unzip && docker-php-ext-install pdo pdo_mysql zip

# 3. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Tetapkan direktori kerja utama di dalam container
WORKDIR /var/www