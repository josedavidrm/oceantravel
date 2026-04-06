<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/autoloader.php';
session_start();
Usuario::verificarSesion('admin');

$usuarioObj = new Usuario();
$db         = Database::getInstance()->getConnection();

$result = $usuarioObj->registrar([
    'nombre'     => $_POST['nombre']     ?? '',
    'apellido'   => $_POST['apellido']   ?? '',
    'email'      => $_POST['email']      ?? '',
    'contrasena' => $_POST['contrasena'] ?? '',
    'telefono'   => $_POST['telefono']   ?? '',
    'rol'        => $_POST['rol']        ?? 'cliente',
]);

// Si es empleado y se creó bien, asignar al hotel
if ($result['success'] && $_POST['rol'] === 'empleado' && !empty($_POST['id_hotel'])) {
    $db->prepare("
        INSERT INTO empleados_hotel (id_usuario, id_hotel, cargo)
        VALUES (?, ?, ?)
    ")->execute([
        $result['id'],
        (int)$_POST['id_hotel'],
        htmlspecialchars($_POST['cargo'] ?? 'Recepcionista'),
    ]);
}

header('Location: ' . SITE_URL . '/pages/admin/usuarios.php?' . ($result['success'] ? 'updated=1' : 'error=1'));
exit;