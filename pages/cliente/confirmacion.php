<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('cliente');

$idReserva = (int)($_GET['id'] ?? 0);
if (!$idReserva) { header('Location: ' . SITE_URL); exit; }

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("
    SELECT r.*, h.nombre AS hotel_nombre, h.sector, h.telefono AS hotel_telefono,
           h.email_contacto AS hotel_email, h.direccion AS hotel_direccion,
           hab.numero AS num_habitacion, th.nombre AS tipo_habitacion,
           u.nombre, u.apellido, u.email,
           p.metodo_pago, p.estado_pago
    FROM reservas r
    INNER JOIN habitaciones hab ON hab.id_habitacion = r.id_habitacion
    INNER JOIN tipo_habitacion th ON th.id_tipo = hab.id_tipo
    INNER JOIN hoteles h ON h.id_hotel = hab.id_hotel
    INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
    LEFT JOIN pagos p ON p.id_reserva = r.id_reserva
    WHERE r.id_reserva = ? AND r.id_usuario = ?
");
$stmt->execute([$idReserva, $_SESSION['id_usuario']]);
$reserva = $stmt->fetch();

if (!$reserva) { header('Location: ' . SITE_URL); exit; }

$noches = (strtotime($reserva['fecha_salida']) - strtotime($reserva['fecha_entrada'])) / 86400;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva confirmada — OceanTravel</title>
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
            <a href="<?= SITE_URL ?>/pages/cliente/mis-reservas.php" class="btn-nav-outline">Mis reservas</a>
            <a href="<?= SITE_URL ?>" class="btn-nav-gold">Inicio</a>
        </div>
    </div>
</nav>

<div style="padding-top:calc(var(--navbar-h) + 3rem);padding-bottom:4rem;">
    <div class="container" style="max-width:700px;">

        <!-- Éxito -->
        <div style="text-align:center;margin-bottom:2.5rem;">
            <div style="font-size:4rem;margin-bottom:1rem;">🎉</div>
            <h1 style="font-size:2rem;color:var(--ocean-deep);margin-bottom:0.5rem;">¡Reserva realizada!</h1>
            <p style="color:var(--text-secondary);">Tu reserva ha sido registrada exitosamente. Te esperamos en <?= htmlspecialchars($reserva['hotel_nombre']) ?>.</p>
        </div>

        <!-- Código de reserva -->
        <div style="background:linear-gradient(135deg,var(--ocean-deep),var(--ocean-bright));border-radius:var(--radius-lg);padding:2rem;text-align:center;margin-bottom:1.5rem;color:white;">
            <p style="font-size:0.85rem;opacity:0.7;margin-bottom:0.5rem;letter-spacing:0.1em;text-transform:uppercase;">Código de reserva</p>
            <div style="font-size:2rem;font-weight:600;font-family:monospace;letter-spacing:0.1em;color:var(--gold-light);">
                <?= htmlspecialchars($reserva['codigo_reserva']) ?>
            </div>
            <p style="font-size:0.8rem;opacity:0.6;margin-top:0.5rem;">Guarda este código para consultar tu reserva</p>
        </div>

        <!-- Detalles -->
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h3>Detalles de la reserva</h3></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <div>
                        <p style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.25rem;">Hotel</p>
                        <strong><?= htmlspecialchars($reserva['hotel_nombre']) ?></strong>
                        <p style="font-size:0.82rem;color:var(--text-secondary);">📍 <?= htmlspecialchars($reserva['hotel_direccion']) ?></p>
                    </div>
                    <div>
                        <p style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.25rem;">Habitación</p>
                        <strong><?= htmlspecialchars($reserva['tipo_habitacion']) ?></strong>
                        <p style="font-size:0.82rem;color:var(--text-secondary);">N° <?= htmlspecialchars($reserva['num_habitacion']) ?></p>
                    </div>
                    <div>
                        <p style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.25rem;">Check-in</p>
                        <strong><?= date('d \d\e F Y', strtotime($reserva['fecha_entrada'])) ?></strong>
                    </div>
                    <div>
                        <p style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.25rem;">Check-out</p>
                        <strong><?= date('d \d\e F Y', strtotime($reserva['fecha_salida'])) ?></strong>
                    </div>
                    <div>
                        <p style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.25rem;">Noches</p>
                        <strong><?= $noches ?> noche<?= $noches > 1 ? 's' : '' ?></strong>
                    </div>
                    <div>
                        <p style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.25rem;">Huéspedes</p>
                        <strong><?= $reserva['num_huespedes'] ?> persona<?= $reserva['num_huespedes'] > 1 ? 's' : '' ?></strong>
                    </div>
                </div>

                <div style="border-top:1px solid var(--gray-200);margin-top:1.25rem;padding-top:1.25rem;display:flex;justify-content:space-between;align-items:center;">
                    <div>
                        <p style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.25rem;">Total a pagar</p>
                        <strong style="font-size:1.5rem;color:var(--ocean-bright);">$<?= number_format($reserva['precio_total'], 2) ?></strong>
                        <?php if ($reserva['descuento_aplicado'] > 0): ?>
                        <span style="font-size:0.82rem;color:var(--success);margin-left:8px;">(-$<?= number_format($reserva['descuento_aplicado'], 2) ?> descuento)</span>
                        <?php endif; ?>
                    </div>
                    <div style="text-align:right;">
                        <span class="badge badge-warning">Pago pendiente</span>
                        <p style="font-size:0.78rem;color:var(--text-secondary);margin-top:0.25rem;">
                            Método: <?= ucfirst(str_replace('_', ' ', $reserva['metodo_pago'] ?? '')) ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de pago -->
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header"><h3>Instrucciones de pago</h3></div>
            <div class="card-body">
                <div class="alert alert-info">
                    ℹ️ Tu reserva está pendiente de confirmación de pago. Por favor realiza el pago y contáctanos con el comprobante.
                </div>
                <?php if ($reserva['hotel_telefono'] || $reserva['hotel_email']): ?>
                <p style="font-size:0.88rem;margin-bottom:0.75rem;">Envía tu comprobante al hotel directamente:</p>
                <?php if ($reserva['hotel_telefono']): ?>
                <a href="https://wa.me/<?= preg_replace('/\D/', '', $reserva['hotel_telefono']) ?>"
                   class="btn btn-outline" style="margin-bottom:0.5rem;display:inline-flex;gap:8px;">
                    📱 WhatsApp: <?= htmlspecialchars($reserva['hotel_telefono']) ?>
                </a><br>
                <?php endif; ?>
                <?php if ($reserva['hotel_email']): ?>
                <a href="mailto:<?= $reserva['hotel_email'] ?>?subject=Comprobante Reserva <?= $reserva['codigo_reserva'] ?>"
                   class="btn btn-outline" style="display:inline-flex;gap:8px;">
                    ✉️ <?= htmlspecialchars($reserva['hotel_email']) ?>
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Acciones -->
        <div style="display:flex;gap:1rem;flex-wrap:wrap;">
            <a href="<?= SITE_URL ?>/pages/cliente/mis-reservas.php" class="btn btn-primary btn-lg">
                📋 Ver mis reservas
            </a>
            <a href="<?= SITE_URL ?>" class="btn btn-outline btn-lg">
                🏠 Volver al inicio
            </a>
            <button onclick="window.print()" class="btn btn-outline btn-lg">
                🖨️ Imprimir
            </button>
        </div>

    </div>
</div>

</body>
</html>
