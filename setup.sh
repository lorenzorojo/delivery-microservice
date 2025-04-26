#!/bin/bash

echo "📄 Copiando archivo .env.example a .env..."
cp .env.example .env

echo "🐳 Levantando servicios con Docker Compose..."
docker-compose up -d --build

echo "🛠 Ejecutando composer install en el contenedor del backend..."
docker-compose exec auth-service composer install --no-dev --optimize-autoloader
docker-compose exec auth-service composer initial-config
docker-compose exec inventory-service composer install --no-dev --optimize-autoloader
docker-compose exec email-service composer install --no-dev --optimize-autoloader
docker-compose exec email-service composer initial-config
docker-compose exec orders-service composer install --no-dev --optimize-autoloader
docker-compose exec api-gateway-service composer install --no-dev --optimize-autoloader

echo "✅ Entorno listo. Puedes iniciar sesión con:"
echo "   📧 Usuario: demo@panel.com"
echo "   🔑 Contraseña: password"
