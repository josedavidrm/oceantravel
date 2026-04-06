<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/autoloader.php';

$db = Database::getInstance()->getConnection();
$hash = password_hash('admin123', PASSWORD_BCRYPT);
$db->prepare("UPDATE usuarios SET contrasena = ? WHERE rol = 'empleado'")->execute([$hash]);

$stmt = $db->query("SELECT nombre, email, estado FROM usuarios WHERE rol = 'empleado'");
foreach ($stmt->fetchAll() as $e) {
    echo "✅ " . $e['nombre'] . " — " . $e['email'] . "<br>";
}
echo "Contraseña actualizada: admin123";
?>