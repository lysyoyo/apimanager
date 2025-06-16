#!/bin/bash

echo "Génération de la documentation Swagger..."
php artisan l5-swagger:generate

if [ $? -ne 0 ]; then
  echo "Échec lors de la génération de la documentation Swagger."
  exit 1
fi

echo "Documentation Swagger générée avec succès."

# Détection automatique de l'URL
LARAVEL_URL="http://127.0.0.1:8000"
SWAGGER_URL="$LARAVEL_URL/api/documentation"

echo "Ouverture de Swagger UI à l'adresse : $SWAGGER_URL"

# Ouvre Swagger UI dans le navigateur selon l'OS
if which xdg-open > /dev/null; then
  xdg-open "$SWAGGER_URL"
elif which open > /dev/null; then
  open "$SWAGGER_URL"
else
  echo "Impossible d'ouvrir automatiquement le navigateur."
fi

echo "Lancement du serveur Laravel..."
php artisan serve
