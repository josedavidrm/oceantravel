<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('empleado');

$db         = Database::getInstance()->getConnection();
$reservaObj = new Reserva();

$stmt = $db->prepare("SELECT id_hotel FROM empleados_hotel WHERE id_usuario = ? AND activo = 1 LIMIT 1");
$stmt->execute([$_SESSION['id_usuario']]);
$asignacion = $stmt->fetch();
if (!$asignacion) { header('Location: ' . SITE_URL . '/pages/acceso-denegado.php'); exit; }

$idHotel   = $asignacion['id_hotel'];
$idReserva = (int)($_GET['id'] ?? 0);
$reserva   = null;

if ($idReserva) {
    $stmt = $db->prepare("
        SELECT r.*, u.nombre, u.apellido, u.email, u.telefono,
               hab.numero AS num_habitacion, hab.id_habitacion,
               th.nombre AS tipo_habitacion
        FROM reservas r
        INNER JOIN habitaciones hab ON hab.id_habitacion = r.id_habitacion
        INNER JOIN tipo_habitacion th ON th.id_tipo = hab.id_tipo
        INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
        WHERE r.id_reserva = ? AND hab.id_hotel = ?
    ");
    $stmt->execute([$idReserva, $idHotel]);
    $reserva = $stmt->fetch();
}

// Procesar check-out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hacer_checkout'])) {
    $idRes = (int)$_POST['id_reserva'];
    $idHab = (int)$_POST['id_habitacion'];
    $estadoHab = $_POST['estado_habitacion'] ?? 'limpieza';

    $reservaObj->cambiarEstado($idRes, 'completada', 'Check-out realizado por ' . $_SESSION['nombre']);
    $db->prepare("UPDATE habitaciones SET estado = ? WHERE id_habitacion = ?")->execute([$estadoHab, $idHab]);

    header('Location: ' . SITE_URL . '/pages/empleado/reservas.php?checkout=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-out — Panel Empleado</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar-empleado.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Check-out</span>
            <div class="topbar-actions">
                <a href="<?= SITE_URL ?>/pages/empleado/reservas.php" class="btn btn-sm btn-outline">← Volver</a>
            </div>
        </div>
        <div class="admin-content">

            <!-- Buscar -->
            <div class="card" style="margin-bottom:1.5rem;">
                <div class="card-header"><h3>Buscar reserva</h3></div>
                <div class="card-body">
                    <form method="GET" style="display:flex;gap:1rem;align-items:flex-end;">
                        <div class="form-group" style="margin:0;flex:1;">
                            <label>Código de reserva</label>
                            <input type="text" name="codigo" class="form-control" placeholder="Ej: OT-2026-00001">
                        </div>
                        <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                    </form>
                    <?php
                    if (!empty($_GET['codigo'])) {
                        $stmt = $db->prepare("SELECT r.id_reserva FROM reservas r INNER JOIN habitaciones hab ON hab.id_habitacion = r.id_habitacion WHERE r.codigo_reserva = ? AND hab.id_hotel = ?");
                        $stmt->execute([$_GET['codigo'], $idHotel]);
                        $found = $stmt->fetch();
                        if ($found) { header('Location: ' . SITE_URL . '/pages/empleado/checkout.php?id=' . $found['id_reserva']); exit; }
                        else { echo '<div class="alert alert-danger" style="margin-top:1rem;">Reserva no encontrada.</div>'; }
                    }
                    ?>
                </div>
            </div>

            <?php if ($reserva): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Registrar check-out</h3>
                    <span class="badge badge-info"><?= ucfirst($reserva['estado']) ?></span>
                </div>
                <div class="card-body">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:2rem;margin-bottom:1.5rem;">
                        <div>
                            <p style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;margin-bottom:0.5rem;">Huésped</p>
                            <strong><?= htmlspecialchars($reserva['nombre'] . ' ' . $reserva['apellido']) ?></strong>
                            <p style="font-size:0.85rem;color:var(--text-secondary);">📞 <?= htmlspecialchars($reserva['telefono'] ?? '—') ?></p>
                        </div>
                        <div>
                            <p style="font-size:0.75rem;color:var(--text-secondary);text-transform:uppercase;margin-bottom:0.5rem;">Habitación</p>
                            <strong><?= htmlspecialchars($reserva['tipo_habitacion']) ?></strong>
                            <p style="font-size:0.85rem;color:var(--text-secondary);">Hab. <?= htmlspecialchars($reserva['num_habitacion']) ?></p>
                            <p style="font-size:0.85rem;color:var(--text-secondary);">Salida: <?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?></p>
                        </div>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="hacer_checkout" value="1">
                        <input type="hidden" name="id_reserva"    value="<?= $reserva['id_reserva'] ?>">
                        <input type="hidden" name="id_habitacion" value="<?= $reserva['id_habitacion'] ?>">

                        <div class="form-group">
                            <label>Estado de la habitación tras el check-out</label>
                            <select name="estado_habitacion" class="form-control">
                                <option value="limpieza">🧹 Enviar a limpieza</option>
                                <option value="libre">✅ Marcar como libre</option>
                                <option value="mantenimiento">🔧 Enviar a mantenimiento</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-gold btn-lg"
                                onclick="return confirm('¿Confirmar check-out de <?= htmlspecialchars($reserva['nombre']) ?>?')">
                            🚪 Confirmar Check-out
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
</body>
</html>
