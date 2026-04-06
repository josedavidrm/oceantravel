<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/autoloader.php';

$db = Database::getInstance()->getConnection();
$hash = password_hash('admin123', PASSWORD_BCRYPT);

$db->prepare("UPDATE usuarios SET contrasena = ? WHERE email = 'maria@oceantravel.com'")->execute([$hash]);

echo "✅ Contraseña de Maria actualizada. Usa: admin123";
?>