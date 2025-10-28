#!/bin/bash

# Script de démarrage optimisé pour Render

# Attendre que la base de données soit prête (Railway)
echo "Attente de la connexion à la base de données Railway..."
max_attempts=30
attempt=1

while [ $attempt -le $max_attempts ]; do
    if php artisan migrate:status > /dev/null 2>&1; then
        echo "Base de données Railway connectée !"
        break
    fi

    echo "Tentative $attempt/$max_attempts - Base de données non prête..."
    sleep 2
    attempt=$((attempt + 1))
done

if [ $attempt -gt $max_attempts ]; then
    echo "Erreur: Impossible de se connecter à la base de données Railway après $max_attempts tentatives"
    exit 1
fi

# Exécuter les migrations si nécessaire
echo "Vérification et exécution des migrations..."
php artisan migrate --force --no-interaction

# Peupler la base de données si elle est vide (optionnel)
if [ "$APP_ENV" = "production" ] && php artisan tinker --execute="echo App\\\Models\\\User::count();" | grep -q "0"; then
    echo "Peuplement de la base de données de production..."
    php artisan db:seed --force --no-interaction
fi

# Générer la documentation Swagger si nécessaire
if [ ! -f storage/api-docs/api-docs.json ] || [ "$L5_SWAGGER_GENERATE_ALWAYS" = "true" ]; then
    echo "Génération de la documentation Swagger..."
    php artisan l5-swagger:generate --no-interaction
fi

# Mettre en cache les configurations
echo "Mise en cache des configurations..."
php artisan config:cache --no-interaction
php artisan route:cache --no-interaction
php artisan view:cache --no-interaction

# Définir les permissions
echo "Configuration des permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

# Démarrer Apache
echo "Démarrage d'Apache..."
apache2-foreground