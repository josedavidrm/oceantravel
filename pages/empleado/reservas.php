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

$idHotel = $asignacion['id_hotel'];
$estado  = $_GET['estado'] ?? '';
$fecha   = $_GET['fecha']  ?? '';

$reservas = $reservaObj->getReservasHotel($idHotel, $estado, $fecha);

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
    <title>Reservas — Panel Empleado</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar-empleado.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Reservas del hotel</span>
        </div>
        <div class="admin-content">

            <!-- Filtros -->
            <div class="card" style="margin-bottom:1.5rem;">
                <div class="card-body">
                    <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
                        <div class="form-group" style="margin:0;">
                            <label>Fecha de entrada</label>
                            <input type="date" name="fecha" class="form-control" value="<?= htmlspecialchars($fecha) ?>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Estado</label>
                            <select name="estado" class="form-control">
                                <option value="">Todos</option>
                                <option value="confirmada" <?= $estado === 'confirmada' ? 'selected' : '' ?>>Confirmadas</option>
                                <option value="pendiente"  <?= $estado === 'pendiente'  ? 'selected' : '' ?>>Pendientes</option>
                                <option value="completada" <?= $estado === 'completada' ? 'selected' : '' ?>>Completadas</option>
                                <option value="cancelada"  <?= $estado === 'cancelada'  ? 'selected' : '' ?>>Canceladas</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                        <a href="<?= SITE_URL ?>/pages/empleado/reservas.php" class="btn btn-outline">Limpiar</a>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Reservas (<?= count($reservas) ?>)</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Huésped</th>
                                    <th>Habitación</th>
                                    <th>Fechas</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reservas)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:2rem;color:var(--text-secondary);">
                                        No hay reservas que mostrar.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($reservas as $r): ?>
                                <tr>
                                    <td>
                                        <strong style="font-family:monospace;font-size:0.82rem;color:var(--ocean-bright);">
                                            <?= htmlspecialchars($r['codigo_reserva']) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></strong><br>
                                        <small style="color:var(--text-secondary);"><?= htmlspecialchars($r['telefono'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        Hab. <?= htmlspecialchars($r['num_habitacion']) ?><br>
                                        <small style="color:var(--text-secondary);"><?= htmlspecialchars($r['tipo_habitacion']) ?></small>
                                    </td>
                                    <td style="font-size:0.82rem;">
                                        📅 <?= date('d/m/Y', strtotime($r['fecha_entrada'])) ?><br>
                                        📅 <?= date('d/m/Y', strtotime($r['fecha_salida'])) ?>
                                    </td>
                                    <td><strong style="color:var(--success);">$<?= number_format($r['precio_total'], 2) ?></strong></td>
                                    <td>
                                        <span class="badge <?= $estadosBadge[$r['estado']] ?? 'badge-info' ?>">
                                            <?= ucfirst($r['estado']) ?>
                                        </span>
                                    </td>
                                    <td style="display:flex;gap:6px;">
                                        <?php if ($r['estado'] === 'confirmada'): ?>
                                        <a href="<?= SITE_URL ?>/pages/empleado/checkin.php?id=<?= $r['id_reserva'] ?>" class="btn btn-sm btn-primary">Check-in</a>
                                        <?php elseif ($r['estado'] === 'pendiente'): ?>
                                        <a href="<?= SITE_URL ?>/pages/empleado/checkin.php?id=<?= $r['id_reserva'] ?>" class="btn btn-sm btn-outline">Ver</a>
                                        <?php else: ?>
                                        <a href="<?= SITE_URL ?>/pages/empleado/checkout.php?id=<?= $r['id_reserva'] ?>" class="btn btn-sm btn-gold">Check-out</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
</body>
</html>
