# Gym System

Sistema web de gestión para gimnasio desarrollado en PHP + MySQL.

## Requisitos

- PHP 8.x
- MySQL 8.x / MariaDB compatible
- Apache con `mod_rewrite`
- Laragon, XAMPP o un servidor PHP equivalente
- Composer (solo si se desea regenerar el manual PDF con Dompdf)

## Instalación rápida en Laragon / XAMPP

1. Clona este repositorio dentro de la carpeta web de tu servidor local.
2. Crea una base de datos llamada `gym_system`.
3. Importa `bk_basededatos.sql` en phpMyAdmin/MySQL. También se incluye `schema.sql` como estructura alternativa.
4. Revisa `app/config/Database.php` y confirma las credenciales de MySQL. Por defecto usa:
   - host: `localhost`
   - base de datos: `gym_system`
   - usuario: `root`
   - contraseña: vacía
5. Enciende Apache y MySQL.
6. Abre una de estas rutas según tu entorno:
   - `http://localhost/COMPLETO-/public/`
   - o el virtual host que hayas configurado.

## Credenciales de prueba

- Email: `admin@gym.com`
- Contraseña: `123456`

Cambia esta contraseña si vas a publicar el sistema fuera de un entorno local.

## Dependencias opcionales

El sistema incluye FPDF y PHP QR en `app/lib/`. Para Dompdf, usado por `generar_manual.php`, ejecuta:

```bash
composer install
```

La carpeta `vendor/` no se versiona porque Composer puede regenerarla desde `composer.lock`.

## Estructura principal

- `app/config/` conexión y migración
- `app/controllers/` controladores
- `app/models/` modelos
- `app/views/` vistas
- `app/lib/` librerías incluidas
- `public/` punto de entrada, CSS e imágenes
- `bk_basededatos.sql` respaldo listo para importar
- `schema.sql` estructura de la base de datos
- `seeder.php` datos de prueba

## Nota

Este repositorio está preparado para ejecución local. No publiques credenciales reales ni expongas `public/reset.php` en producción.
