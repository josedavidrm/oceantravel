<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('cliente');

$reservaObj = new Reserva();
$reservas   = $reservaObj->getReservasUsuario($_SESSION['id_usuario']);

$estadosBadge = [
    'pendiente'  => 'badge-warning',
    'confirmada' => 'badge-success',
    'cancelada'  => 'badge-danger',
    'completada' => 'badge-info',
    'no_show'    => 'badge-danger',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis reservas — OceanTravel</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>

<nav class="navbar solid">
    <div class="container navbar-inner">
        <a href="<?= SITE_URL ?>" class="nav-logo">
            <div class="nav-logo-icon">🌊</div>
            <div class="nav-logo-text">Ocean<span>Travel</span></div>
        </a>
        <div class="nav-actions">
            <a href="<?= SITE_URL ?>" class="btn-nav-outline">Inicio</a>
            <a href="<?= SITE_URL ?>/pages/logout.php" class="btn-nav-outline">Cerrar sesión</a>
        </div>
    </div>
</nav>

<div style="padding-top:calc(var(--navbar-h) + 2rem);padding-bottom:4rem;">
    <div class="container">

        <div style="margin-bottom:2rem;">
            <h1 style="font-size:1.8rem;">Mis reservas</h1>
            <p style="color:var(--text-secondary);">Hola, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>. Aquí están todas tus reservas.</p>
        </div>

        <?php if (empty($reservas)): ?>
        <div style="text-align:center;padding:4rem 2rem;">
            <div style="font-size:4rem;margin-bottom:1rem;">🏖️</div>
            <h2 style="font-size:1.5rem;margin-bottom:0.75rem;">Aún no tienes reservas</h2>
            <p style="color:var(--text-secondary);margin-bottom:1.5rem;">Explora nuestros hoteles y reserva tu próxima estadía en Margarita.</p>
            <a href="<?= SITE_URL ?>" class="btn btn-primary btn-lg">Ver hoteles disponibles</a>
        </div>
        <?php else: ?>

        <div style="display:flex;flex-direction:column;gap:1.25rem;">
            <?php foreach ($reservas as $r): ?>
            <div class="card">
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:auto 1fr auto;gap:1.5rem;align-items:start;flex-wrap:wrap;">

                        <!-- Imagen -->
                        <div style="width:120px;height:90px;border-radius:var(--radius-sm);overflow:hidden;background:linear-gradient(135deg,var(--ocean-deep),var(--ocean-bright));flex-shrink:0;">
                            <?php if ($r['foto_portada']): ?>
                            <img src="<?= UPLOAD_URL ?>hoteles/<?= $r['id_hotel'] ?? '' ?>/<?= htmlspecialchars($r['foto_portada']) ?>"
                                 style="width:100%;height:100%;object-fit:cover;" alt="<?= htmlspecialchars($r['hotel_nombre']) ?>">
                            <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:2rem;">🏨</div>
                            <?php endif; ?>
                        </div>

                        <!-- Info -->
                        <div>
                            <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;margin-bottom:0.5rem;">
                                <strong style="font-size:1.05rem;"><?= htmlspecialchars($r['hotel_nombre']) ?></strong>
                                <span class="badge <?= $estadosBadge[$r['estado']] ?? 'badge-info' ?>">
                                    <?= ucfirst($r['estado']) ?>
                                </span>
                            </div>
                            <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:0.4rem;">
                                📍 <?= htmlspecialchars($r['sector'] ?? '') ?> &nbsp;·&nbsp;
                                🛏️ <?= htmlspecialchars($r['tipo_habitacion']) ?> — Hab. <?= htmlspecialchars($r['num_habitacion']) ?>
                            </p>
                            <p style="font-size:0.85rem;color:var(--text-secondary);">
                                📅 <?= date('d/m/Y', strtotime($r['fecha_entrada'])) ?> →
                                <?= date('d/m/Y', strtotime($r['fecha_salida'])) ?>
                                &nbsp;·&nbsp;
                                <?= (strtotime($r['fecha_salida']) - strtotime($r['fecha_entrada'])) / 86400 ?> noche(s)
                            </p>
                            <p style="font-size:0.78rem;color:var(--text-secondary);margin-top:0.3rem;">
                                Código: <code style="background:var(--gray-100);padding:0.1rem 0.4rem;border-radius:4px;">
                                    <?= htmlspecialchars($r['codigo_reserva']) ?>
                                </code>
                            </p>
                        </div>

                        <!-- Precio y acciones -->
                        <div style="text-align:right;flex-shrink:0;">
                            <strong style="font-size:1.2rem;color:var(--ocean-bright);">
                                $<?= number_format($r['precio_total'], 2) ?>
                            </strong>
                            <p style="font-size:0.78rem;color:var(--text-secondary);margin-bottom:0.75rem;">
                                Pago: <?= ucfirst($r['estado_pago'] ?? 'pendiente') ?>
                            </p>
                            <?php if ($r['estado'] === 'completada'): ?>
                            <a href="<?= SITE_URL ?>/pages/cliente/resena.php?id=<?= $r['id_reserva'] ?>"
                               class="btn btn-sm btn-gold">⭐ Dejar reseña</a>
                            <?php elseif ($r['estado'] === 'pendiente' || $r['estado'] === 'confirmada'): ?>
                            <a href="<?= SITE_URL ?>/pages/cliente/confirmacion.php?id=<?= $r['id_reserva'] ?>"
                               class="btn btn-sm btn-outline">Ver detalles</a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>
</div>

</body>
</html>
