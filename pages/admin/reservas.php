<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('admin');

$db = Database::getInstance()->getConnection();

// Confirmar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_pago'])) {
    $db->prepare("UPDATE pagos SET estado_pago = 'completado', fecha_pago = NOW() WHERE id_reserva = ?")
       ->execute([(int)$_POST['id_reserva']]);
    $db->prepare("UPDATE reservas SET estado = 'confirmada' WHERE id_reserva = ?")
       ->execute([(int)$_POST['id_reserva']]);
    header('Location: ' . SITE_URL . '/pages/admin/reservas.php?updated=1');
    exit;
}

// Cambiar estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $reservaObj = new Reserva();
    $reservaObj->cambiarEstado((int)$_POST['id_reserva'], $_POST['nuevo_estado']);
    header('Location: ' . SITE_URL . '/pages/admin/reservas.php?updated=1');
    exit;
}

// Filtros
$estado = $_GET['estado'] ?? '';
$buscar = $_GET['buscar'] ?? '';

$sql = "
    SELECT r.*, u.nombre, u.apellido, u.email, u.telefono,
           hab.numero AS num_habitacion, th.nombre AS tipo_habitacion,
           h.nombre AS hotel_nombre, h.sector,
           p.estado_pago, p.metodo_pago, p.id_pago
    FROM reservas r
    INNER JOIN habitaciones hab ON hab.id_habitacion = r.id_habitacion
    INNER JOIN tipo_habitacion th ON th.id_tipo = hab.id_tipo
    INNER JOIN hoteles h ON h.id_hotel = hab.id_hotel
    INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
    LEFT JOIN pagos p ON p.id_reserva = r.id_reserva
    WHERE 1=1
";
$params = [];

if ($estado) { $sql .= " AND r.estado = ?"; $params[] = $estado; }
if ($buscar) { $sql .= " AND (u.nombre LIKE ? OR u.email LIKE ? OR r.codigo_reserva LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }

$sql .= " ORDER BY r.fecha_creacion DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll();

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
    <title>Reservas — OceanTravel Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Gestión de reservas</span>
        </div>
        <div class="admin-content">

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">✅ Reserva actualizada correctamente.</div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="card" style="margin-bottom:1.5rem;">
                <div class="card-body">
                    <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
                        <div class="form-group" style="margin:0;flex:1;min-width:200px;">
                            <label>Buscar</label>
                            <input type="text" name="buscar" class="form-control"
                                   placeholder="Nombre, email o código..."
                                   value="<?= htmlspecialchars($buscar) ?>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Estado</label>
                            <select name="estado" class="form-control">
                                <option value="">Todos</option>
                                <option value="pendiente"  <?= $estado === 'pendiente'  ? 'selected' : '' ?>>Pendientes</option>
                                <option value="confirmada" <?= $estado === 'confirmada' ? 'selected' : '' ?>>Confirmadas</option>
                                <option value="cancelada"  <?= $estado === 'cancelada'  ? 'selected' : '' ?>>Canceladas</option>
                                <option value="completada" <?= $estado === 'completada' ? 'selected' : '' ?>>Completadas</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                        <a href="<?= SITE_URL ?>/pages/admin/reservas.php" class="btn btn-outline">Limpiar</a>
                    </form>
                </div>
            </div>

            <!-- Tabla -->
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
                                    <th>Cliente</th>
                                    <th>Hotel / Habitación</th>
                                    <th>Fechas</th>
                                    <th>Total</th>
                                    <th>Estado reserva</th>
                                    <th>Pago</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reservas)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:2rem;color:var(--text-secondary);">
                                        No hay reservas que mostrar.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($reservas as $r): ?>
                                <tr>
                                    <td>
                                        <strong style="font-family:monospace;font-size:0.82rem;color:var(--ocean-bright);">
                                            <?= htmlspecialchars($r['codigo_reserva']) ?>
                                        </strong><br>
                                        <small style="color:var(--text-secondary);">
                                            <?= date('d/m/Y', strtotime($r['fecha_creacion'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></strong><br>
                                        <small style="color:var(--text-secondary);"><?= htmlspecialchars($r['email']) ?></small><br>
                                        <small style="color:var(--text-secondary);"><?= htmlspecialchars($r['telefono'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($r['hotel_nombre']) ?></strong><br>
                                        <small style="color:var(--text-secondary);">
                                            Hab. <?= htmlspecialchars($r['num_habitacion']) ?> — <?= htmlspecialchars($r['tipo_habitacion']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            📅 <?= date('d/m/Y', strtotime($r['fecha_entrada'])) ?><br>
                                            📅 <?= date('d/m/Y', strtotime($r['fecha_salida'])) ?><br>
                                            <?php $noches = (strtotime($r['fecha_salida']) - strtotime($r['fecha_entrada'])) / 86400; ?>
                                            <span style="color:var(--text-secondary);"><?= $noches ?> noche(s)</span>
                                        </small>
                                    </td>
                                    <td>
                                        <strong style="color:var(--success);">$<?= number_format($r['precio_total'], 2) ?></strong>
                                        <?php if ($r['descuento_aplicado'] > 0): ?>
                                        <br><small style="color:var(--gold);">-$<?= number_format($r['descuento_aplicado'], 2) ?> dto.</small>
                                        <?php endif; ?>
                                        <?php if ($r['metodo_pago']): ?>
                                        <br><small style="color:var(--text-secondary);"><?= ucfirst(str_replace('_',' ',$r['metodo_pago'])) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $estadosBadge[$r['estado']] ?? 'badge-info' ?>">
                                            <?= ucfirst($r['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($r['estado_pago'] === 'completado'): ?>
                                            <span class="badge badge-success">✅ Pagado</span>
                                        <?php else: ?>
                                            <div style="display:flex;flex-direction:column;gap:4px;">
                                                <span class="badge badge-warning" style="margin-bottom:4px;">
                                                    <?= $r['estado_pago'] ? ucfirst($r['estado_pago']) : 'Sin pago' ?>
                                                </span>
                                                <?php if ($r['estado'] !== 'cancelada' && $r['estado'] !== 'no_show'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="confirmar_pago" value="1">
                                                    <input type="hidden" name="id_reserva" value="<?= $r['id_reserva'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-gold"
                                                            onclick="return confirm('¿Confirmar el pago de $<?= number_format($r['precio_total'], 2) ?> de <?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?>?')">
                                                        💰 Confirmar pago
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline"
                                                onclick="cambiarEstado(<?= $r['id_reserva'] ?>, '<?= $r['estado'] ?>')">
                                            ✏️ Estado
                                        </button>
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

<!-- Modal cambiar estado -->
<div id="modalEstado" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--white);border-radius:var(--radius-md);padding:2rem;width:100%;max-width:400px;margin:1rem;">
        <h3 style="margin-bottom:1.5rem;font-family:'Poppins',sans-serif;">Cambiar estado de reserva</h3>
        <form method="POST">
            <input type="hidden" name="cambiar_estado" value="1">
            <input type="hidden" name="id_reserva" id="modalIdReserva">
            <div class="form-group">
                <label>Nuevo estado</label>
                <select name="nuevo_estado" id="modalNuevoEstado" class="form-control">
                    <option value="pendiente">Pendiente</option>
                    <option value="confirmada">Confirmada</option>
                    <option value="cancelada">Cancelada</option>
                    <option value="completada">Completada</option>
                    <option value="no_show">No show</option>
                </select>
            </div>
            <div style="display:flex;gap:1rem;margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary btn-block">Guardar</button>
                <button type="button" class="btn btn-outline btn-block" onclick="cerrarModal()">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function cambiarEstado(id, estadoActual) {
    document.getElementById('modalIdReserva').value = id;
    document.getElementById('modalNuevoEstado').value = estadoActual;
    document.getElementById('modalEstado').style.display = 'flex';
}
function cerrarModal() {
    document.getElementById('modalEstado').style.display = 'none';
}
</script>
</body>
</html>