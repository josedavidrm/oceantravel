<?php
// crear-usuario.php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/autoloader.php';
session_start();
Usuario::verificarSesion('admin');

$usuarioObj = new Usuario();
$result = $usuarioObj->registrar([
    'nombre'     => $_POST['nombre']     ?? '',
    'apellido'   => $_POST['apellido']   ?? '',
    'email'      => $_POST['email']      ?? '',
    'contrasena' => $_POST['contrasena'] ?? '',
    'telefono'   => $_POST['telefono']   ?? '',
    'rol'        => $_POST['rol']        ?? 'cliente',
]);

header('Location: ' . SITE_URL . '/pages/admin/usuarios.php?' . ($result['success'] ? 'updated=1' : 'error=1'));
exit;
