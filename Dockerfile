# 1. On part d'une image PHP 8.2 officielle avec FPM (le moteur PHP)
FROM php:8.2-fpm

# 2. On installe les outils système nécessaires (Git, Libs pour images, etc.)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# 3. On installe les extensions PHP dont Laravel a besoin pour fonctionner
# (Celles-ci permettent à PHP de parler à MySQL et de gérer le texte/images)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# 4. On récupère "Composer" (le gestionnaire de paquets PHP) depuis son image officielle
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. On définit le dossier de travail à l'intérieur de la "boîte"
WORKDIR /var/www

# 6. On copie tout ton projet dans la boîte
COPY . /var/www

# 7. On donne les permissions à Laravel pour qu'il puisse écrire des logs
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# 8. On expose le port 9000 (celui qu'utilise PHP-FPM par défaut)
EXPOSE 9000

CMD ["php-fpm"]