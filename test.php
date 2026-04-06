<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/autoloader.php';

$db   = Database::getInstance()->getConnection();
$hash = password_hash('admin123', PASSWORD_BCRYPT);

// Actualizar TODOS los usuarios empleados
$db->prepare("UPDATE usuarios SET contrasena = ? WHERE rol = 'empleado'")->execute([$hash]);

// Mostrar todos los empleados
$stmt = $db->query("SELECT id_usuario, nombre, email, rol, estado FROM usuarios WHERE rol = 'empleado'");
$empleados = $stmt->fetchAll();

echo "<h3>Empleados actualizados:</h3>";
foreach ($empleados as $e) {
    echo "✅ " . $e['nombre'] . " — " . $e['email'] . " — Estado: " . $e['estado'] . "<br>";
}
echo "<br><strong>Contraseña para todos: admin123</strong>";
?>