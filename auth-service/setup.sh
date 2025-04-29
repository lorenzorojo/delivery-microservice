#!/bin/bash

# Este script se ejecuta al iniciar el contenedor de Docker para configurar el entorno de desarrollo.
echo "🐳 Levantando servicios con Docker Compose..."
docker-compose up -d --build

echo "🛠 Ejecutando composer install en el contenedor del backend..."
docker-compose exec auth_service composer install --optimize-autoloader --prefer-dist
echo "🔄 Ejecutando migraciones y seeders..."
docker-compose exec auth_service composer initial-config

echo "✅ Entorno listo. Puedes iniciar sesión con:"
echo "   📧 Usuario: demo@panel.com"
echo "   🔑 Contraseña: password"
