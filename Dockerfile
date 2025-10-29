# Étape 1 : build avec Composer
FROM composer:2.7 AS build

WORKDIR /app

# Copier les fichiers composer
COPY composer.json composer.lock ./

# Installer les dépendances sans les dev et avec autoloader optimisé
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Copier le reste du code
COPY . .

# Étape 2 : runtime
FROM php:8.3-fpm

# Installer les dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev curl && \
    docker-php-ext-install pdo pdo_pgsql && \
    rm -rf /var/lib/apt/lists/*

# Créer le groupe et l'utilisateur non-root
RUN groupadd -g 1000 laravel && \
    useradd -u 1000 -g laravel -m -s /bin/bash laravel

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le code et les dépendances depuis l’étape build
COPY --from=build /app ./

# Copier le script d’entrée et le rendre exécutable
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Changer le propriétaire du code vers l’utilisateur non-root
RUN chown -R laravel:laravel /var/www/html

# Passer à l’utilisateur non-root
USER laravel

# Exposer le port
EXPOSE 8000

# Entrypoint et commande par défaut
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
