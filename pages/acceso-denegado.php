<?php
require_once __DIR__ . '/../config/database.php';
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Acceso denegado — OceanTravel</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body style="display:flex;align-items:center;justify-content:center;min-height:100vh;background:var(--gray-100);">
    <div style="text-align:center;padding:3rem;">
        <div style="font-size:4rem;margin-bottom:1rem;">⛔</div>
        <h1 style="font-size:1.8rem;margin-bottom:0.5rem;">Acceso denegado</h1>
        <p style="color:var(--text-secondary);margin-bottom:2rem;">No tienes permisos para ver esta página.</p>
        <a href="<?= SITE_URL ?>" class="btn btn-primary">Volver al inicio</a>
    </div>
</body>
</html>