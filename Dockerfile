
# Étape 1: Build des dépendances PHP (utiliser PHP 8.3 CLI pour la compatibilité avec composer.lock)
FROM php:8.3-cli-alpine AS composer-build

WORKDIR /app

# Installer utilitaires et composer
RUN apk add --no-cache curl git unzip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copier les fichiers de dépendances
COPY composer.json composer.lock ./

# Installer les dépendances PHP sans scripts post-install (build stage)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Étape 2: Image finale pour l'application (dev-friendly using CLI to run artisan serve)
FROM php:8.3-cli-alpine

# Installer les extensions PHP nécessaires et les outils Postgres
RUN apk add --no-cache postgresql-dev postgresql-client curl git unzip \
    && docker-php-ext-install pdo pdo_pgsql

# Installer Composer dans l'image finale pour permettre l'exécution au runtime
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Créer un utilisateur non-root
RUN addgroup -g 1000 laravel && adduser -G laravel -g laravel -s /bin/sh -D laravel

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les dépendances installées depuis l'étape de build
COPY --from=composer-build /app/vendor ./vendor

# Copier le reste du code de l'application
COPY . .

# Créer les répertoires nécessaires et définir les permissions
RUN mkdir -p storage/framework/{cache,data,sessions,testing,views} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && chown -R laravel:laravel /var/www/html \
    && chmod -R 775 storage bootstrap/cache

# Copier le script d'entrée
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Passer à l'utilisateur non-root
USER laravel

# Exposer le port 8000 (dev)
EXPOSE 8000

# Commande par défaut (dev) — artisan serve sur le port 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]