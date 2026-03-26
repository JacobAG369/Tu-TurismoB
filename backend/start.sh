#!/bin/bash

# Ejecutar descubrimiento de paquetes ahora que hay variables de entorno (.env de Render)
php artisan package:discover --ansi

# Generar cache para producción (opcional pero recomendado)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Iniciar Apache en primer plano
apache2-foreground
