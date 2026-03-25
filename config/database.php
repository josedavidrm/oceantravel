<?php
// ============================================================
//  OceanTravel — Configuración de Base de Datos
//  Agencia de reservas | Isla de Margarita, Venezuela
// ============================================================

define('DB_HOST',     'localhost');
define('DB_NAME',     'oceantravel');
define('DB_USER',     'root');         // Cambiar en producción
define('DB_PASS',     '');             // Cambiar en producción
define('DB_CHARSET',  'utf8mb4');

// Configuración general del sistema
define('SITE_NAME',   'OceanTravel');
define('SITE_URL', 'http://localhost:8012/oceantravel');
define('UPLOAD_PATH', __DIR__ . '/../public/images/uploads/');
define('UPLOAD_URL',  SITE_URL . '/public/images/uploads/');

// Tipos de archivo permitidos para fotos
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Zona horaria Venezuela
date_default_timezone_set('America/Caracas');
