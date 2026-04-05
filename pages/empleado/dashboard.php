<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('empleado');

$db            = Database::getInstance()->getConnection();
$habitacionObj = new Habitacion();
$reservaObj    = new Reserva();

// Obtener hotel asignado al empleado
$stmt = $db->prepare("SELECT eh.*, h.nombre AS hotel_nombre, h.sector FROM empleados_hotel eh INNER JOIN hoteles h ON h.id_hotel = eh.id_hotel WHERE eh.id_usuario = ? AND eh.activo = 1 LIMIT 1");
$stmt->execute([$_SESSION['id_usuario']]);
$asignacion = $stmt->fetch();

if (!$asignacion) {
    header('Location: ' . SITE_URL . '/pages/acceso-denegado.php');
    exit;
}

$idHotel     = $asignacion['id_hotel'];
$resumen     = $habitacionObj->getResumenEstados($idHotel);
$reservasHoy = $reservaObj->getReservasHotel($idHotel, '', date('Y-m-d'));
$reservasPendientes = $reservaObj->getReservasHotel($idHotel, 'confirmada');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Empleado — OceanTravel</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar-empleado.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Dashboard — <?= htmlspecialchars($asignacion['hotel_nombre']) ?></span>
            <div class="topbar-actions">
                <span style="font-size:0.85rem;color:var(--text-secondary);">
                    Bienvenido, <strong><?= htmlspecialchars($_SESSION['nombre']) ?></strong>
                </span>
                <a href="<?= SITE_URL ?>/pages/logout.php" class="btn btn-sm btn-outline" style="color:var(--danger);border-color:var(--danger);">
                    Cerrar sesión
                </a>
            </div>
        </div>

        <div class="admin-content">

            <!-- Stats habitaciones -->
            <div class="stats-grid" style="margin-bottom:1.5rem;">
                <div class="stat-card">
                    <div class="stat-icon green">✅</div>
                    <div class="stat-info">
                        <strong><?= $resumen['libre'] ?></strong>
                        <span>Habitaciones libres</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon red">🔴</div>
                    <div class="stat-info">
                        <strong><?= $resumen['ocupada'] ?></strong>
                        <span>Habitaciones ocupadas</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon amber">🧹</div>
                    <div class="stat-info">
                        <strong><?= $resumen['limpieza'] ?></strong>
                        <span>En limpieza</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gray">🔧</div>
                    <div class="stat-info">
                        <strong><?= $resumen['mantenimiento'] ?></strong>
                        <span>En mantenimiento</span>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

                <!-- Reservas de hoy -->
                <div class="card">
                    <div class="card-header">
                        <h3>Llegadas de hoy — <?= date('d/m/Y') ?></h3>
                        <span class="badge badge-info"><?= count($reservasHoy) ?></span>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($reservasHoy)): ?>
                        <p style="text-align:center;padding:2rem;color:var(--text-secondary);">
                            No hay llegadas programadas para hoy.
                        </p>
                        <?php else: ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Huésped</th>
                                        <th>Habitación</th>
                                        <th>Estado</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($reservasHoy as $r): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></strong><br>
                                        <small style="color:var(--text-secondary);"><?= htmlspecialchars($r['email']) ?></small>
                                    </td>
                                    <td>Hab. <?= htmlspecialchars($r['num_habitacion']) ?><br>
                                        <small style="color:var(--text-secondary);"><?= htmlspecialchars($r['tipo_habitacion']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $r['estado'] === 'confirmada' ? 'badge-success' : 'badge-warning' ?>">
                                            <?= ucfirst($r['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= SITE_URL ?>/pages/empleado/checkin.php?id=<?= $r['id_reserva'] ?>"
                                           class="btn btn-sm btn-primary">
                                            Check-in
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones rápidas -->
                <div class="card">
                    <div class="card-header"><h3>Acciones rápidas</h3></div>
                    <div class="card-body" style="display:flex;flex-direction:column;gap:0.75rem;">
                        <a href="<?= SITE_URL ?>/pages/empleado/habitaciones.php" class="btn btn-primary btn-block">
                            🛏️ Ver estado de habitaciones
                        </a>
                        <a href="<?= SITE_URL ?>/pages/empleado/reservas.php" class="btn btn-outline btn-block">
                            📋 Ver todas las reservas
                        </a>
                        <a href="<?= SITE_URL ?>/pages/empleado/incidencias.php" class="btn btn-outline btn-block">
                            🔧 Reportar incidencia
                        </a>
                        <a href="<?= SITE_URL ?>/pages/empleado/checkout.php" class="btn btn-gold btn-block">
                            🚪 Registrar check-out
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>
</body>
</html>
