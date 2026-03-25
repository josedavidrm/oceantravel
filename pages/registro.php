<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/autoloader.php';

session_start();

if (isset($_SESSION['id_usuario'])) {
    header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = new Usuario();
    $result  = $usuario->registrar([
        'nombre'     => $_POST['nombre']     ?? '',
        'apellido'   => $_POST['apellido']   ?? '',
        'email'      => $_POST['email']      ?? '',
        'contrasena' => $_POST['contrasena'] ?? '',
        'telefono'   => $_POST['telefono']   ?? '',
        'rol'        => 'cliente',
    ]);

    if ($result['success']) {
        $success = '¡Cuenta creada exitosamente! Ya puedes iniciar sesión.';
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear cuenta — OceanTravel</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>

<div class="auth-page centered">

    <div class="auth-card">

        <!-- Logo -->
        <div class="nav-logo" style="margin-bottom:1.5rem;">
            <div class="nav-logo-icon">🌊</div>
            <div class="nav-logo-text">Ocean<span>Travel</span></div>
        </div>

        <h2 class="auth-title">Crear cuenta</h2>
        <p class="auth-subtitle">Únete y reserva tu estadía en Margarita</p>

        <?php if ($error):   ?><div class="alert alert-danger"> <?= htmlspecialchars($error)   ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="">

            <div class="auth-row">
                <div class="auth-field">
                    <label>Nombre</label>
                    <div class="input-icon">
                        <input type="text" name="nombre" placeholder="Juan"
                               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="auth-field">
                    <label>Apellido</label>
                    <div class="input-icon">
                        <input type="text" name="apellido" placeholder="Pérez"
                               value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>" required>
                    </div>
                </div>
            </div>

            <div class="auth-field">
                <label>Correo electrónico</label>
                <div class="input-icon">
                    <input type="email" name="email" placeholder="tu@correo.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="auth-field">
                <label>Teléfono (opcional)</label>
                <div class="input-icon">
                    <input type="tel" name="telefono" placeholder="+58 424-0000000"
                           value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                </div>
            </div>

            <div class="auth-field">
                <label>Contraseña</label>
                <div class="input-icon">
                    <input type="password" name="contrasena" placeholder="Mínimo 8 caracteres" required>
                </div>
            </div>

            <button type="submit" class="btn-auth" style="margin-top:0.5rem;">
                Crear mi cuenta
            </button>

        </form>
        <?php endif; ?>

        <div class="auth-divider"><span>¿Ya tienes cuenta?</span></div>
        <p class="auth-alt">
            <a href="<?= SITE_URL ?>/pages/login.php">Iniciar sesión</a>
        </p>

    </div>

</div>

</body>
</html>