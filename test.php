<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/autoloader.php';

$db = Database::getInstance()->getConnection();
$hash = password_hash('admin123', PASSWORD_BCRYPT);
$db->prepare("UPDATE usuarios SET contrasena = ? WHERE email = 'admin@oceantravel.com'")->execute([$hash]);

echo "Listo! Contraseña actualizada a: admin123";
?>
```

Guárdalo en `C:\xampp\htdocs\oceantravel\test.php` y abre:
```
http://localhost:8012/oceantravel/test.php