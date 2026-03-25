<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/autoloader.php';

session_start();

// Si ya está logueado, redirigir según rol
if (isset($_SESSION['id_usuario'])) {
    if ($_SESSION['rol'] === 'admin')        header('Location: ' . SITE_URL . '/pages/admin/dashboard.php');
    elseif ($_SESSION['rol'] === 'empleado') header('Location: ' . SITE_URL . '/pages/empleado/dashboard.php');
    else                                      header('Location: ' . SITE_URL . '/index.php');
    exit;
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = new Usuario();
    $result  = $usuario->login($_POST['email'] ?? '', $_POST['contrasena'] ?? '');

    if ($result['success']) {
        if ($result['rol'] === 'admin')        header('Location: ' . SITE_URL . '/pages/admin/dashboard.php');
        elseif ($result['rol'] === 'empleado') header('Location: ' . SITE_URL . '/pages/empleado/dashboard.php');
        else                                    header('Location: ' . SITE_URL . '/index.php');
        exit;
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
    <title>Iniciar sesión — OceanTravel</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>

<div class="auth-page">

    <!-- ── Panel visual izquierdo ── -->
    <div class="auth-visual">
        <div class="auth-bubbles">
            <div class="auth-bubble"></div>
            <div class="auth-bubble"></div>
            <div class="auth-bubble"></div>
        </div>
        <div class="auth-waves">
            <div class="auth-wave"></div>
            <div class="auth-wave"></div>
        </div>
        <div class="auth-content">
            <div class="nav-logo">
                <div class="nav-logo-icon">🌊</div>
                <div class="nav-logo-text">Ocean<span>Travel</span></div>
            </div>
            <h1 class="auth-tagline">
                La perla del Caribe<br>
                te espera en<br>
                <em>Isla de Margarita</em>
            </h1>
            <p class="auth-sub">Agencia de reservas hoteleras · Venezuela</p>
        </div>
    </div>

    <!-- ── Panel formulario derecho ── -->
    <div class="auth-panel">
        <div class="auth-form-inner">

            <h2 class="auth-title">Bienvenido</h2>
            <p class="auth-subtitle">Inicia sesión para continuar</p>

            <?php if ($error):   ?><div class="alert alert-danger"> <?= htmlspecialchars($error)   ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <form method="POST" action="">

                <div class="auth-field">
                    <label>Correo electrónico</label>
                    <div class="input-icon">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                        <input type="email" name="email" placeholder="tu@correo.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                    </div>
                </div>

                <div class="auth-field">
                    <label>Contraseña</label>
                    <div class="input-icon">
                        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input type="password" name="contrasena" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="auth-forgot">
                    <a href="#">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="btn-auth">Iniciar sesión</button>

            </form>

            <div class="auth-divider"><span>¿No tienes cuenta?</span></div>

            <p class="auth-alt">
                <a href="<?= SITE_URL ?>/pages/registro.php">Crear cuenta gratis</a>
            </p>

        </div>
    </div>

</div>

</body>
</html>