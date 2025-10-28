#!/bin/bash

# Attendre que la base de données soit prête
echo "Attente de la base de données..."
if [ "$DB_CONNECTION" = "pgsql" ]; then
    while ! pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE"; do
        echo "PostgreSQL n'est pas encore prêt..."
        sleep 2
    done
    echo "PostgreSQL est prêt !"
elif [ "$DB_CONNECTION" = "mysql" ]; then
    while ! mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent; do
        echo "MySQL n'est pas encore prêt..."
        sleep 2
    done
    echo "MySQL est prêt !"
else
    echo "Type de base de données non supporté ou connexion externe détectée. Passage à l'étape suivante..."
fi

# Générer la clé d'application si elle n'existe pas
if [ ! -f /var/www/html/.env ]; then
    echo "Création du fichier .env..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Générer la clé d'application
echo "Génération de la clé d'application..."
php artisan key:generate

# Exécuter les migrations
echo "Exécution des migrations..."
php artisan migrate --force

# Peupler la base de données (optionnel)
if [ "$APP_ENV" = "local" ]; then
    echo "Peuplement de la base de données..."
    php artisan db:seed --force
fi

# Générer la documentation Swagger
echo "Génération de la documentation Swagger..."
php artisan l5-swagger:generate

# Mettre en cache les configurations, routes et vues
echo "Mise en cache des configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Définir les permissions
echo "Configuration des permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/bootstrap/cache

# Démarrer Apache
echo "Démarrage d'Apache..."
apache2-foreground