<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('empleado');

$db            = Database::getInstance()->getConnection();
$habitacionObj = new Habitacion();

$stmt = $db->prepare("SELECT id_hotel FROM empleados_hotel WHERE id_usuario = ? AND activo = 1 LIMIT 1");
$stmt->execute([$_SESSION['id_usuario']]);
$asignacion = $stmt->fetch();
if (!$asignacion) { header('Location: ' . SITE_URL . '/pages/acceso-denegado.php'); exit; }

$idHotel = $asignacion['id_hotel'];

// Cambiar estado habitación
if (isset($_GET['id']) && isset($_GET['estado'])) {
    $habitacionObj->cambiarEstado((int)$_GET['id'], $_GET['estado']);
    header('Location: ' . SITE_URL . '/pages/empleado/habitaciones.php?updated=1');
    exit;
}

$habitaciones = $habitacionObj->listarPorHotel($idHotel);
$resumen      = $habitacionObj->getResumenEstados($idHotel);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habitaciones — Panel Empleado</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar-empleado.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Estado de habitaciones</span>
        </div>
        <div class="admin-content">

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">✅ Estado actualizado.</div>
            <?php endif; ?>

            <!-- Resumen -->
            <div class="stats-grid" style="margin-bottom:1.5rem;">
                <div class="stat-card"><div class="stat-icon green">✅</div><div class="stat-info"><strong><?= $resumen['libre'] ?></strong><span>Libres</span></div></div>
                <div class="stat-card"><div class="stat-icon red">🔴</div><div class="stat-info"><strong><?= $resumen['ocupada'] ?></strong><span>Ocupadas</span></div></div>
                <div class="stat-card"><div class="stat-icon amber">🧹</div><div class="stat-info"><strong><?= $resumen['limpieza'] ?></strong><span>Limpieza</span></div></div>
                <div class="stat-card"><div class="stat-icon gray">🔧</div><div class="stat-info"><strong><?= $resumen['mantenimiento'] ?></strong><span>Mantenimiento</span></div></div>
            </div>

            <!-- Grid de habitaciones -->
            <div class="card">
                <div class="card-header"><h3>Todas las habitaciones</h3></div>
                <div class="card-body">
                    <div class="room-status-grid">
                        <?php foreach ($habitaciones as $hab): ?>
                        <div class="room-item <?= $hab['estado'] ?>" onclick="cambiarEstado(<?= $hab['id_habitacion'] ?>, '<?= $hab['estado'] ?>')">
                            <div class="room-number"><?= htmlspecialchars($hab['numero']) ?></div>
                            <div class="room-type"><?= htmlspecialchars(substr($hab['tipo_nombre'], 0, 12)) ?></div>
                            <div class="room-status-dot"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Leyenda -->
                    <div style="display:flex;gap:1.5rem;margin-top:1.5rem;flex-wrap:wrap;font-size:0.82rem;">
                        <span style="display:flex;align-items:center;gap:6px;"><span style="width:12px;height:12px;border-radius:50%;background:var(--success);display:inline-block;"></span> Libre</span>
                        <span style="display:flex;align-items:center;gap:6px;"><span style="width:12px;height:12px;border-radius:50%;background:var(--danger);display:inline-block;"></span> Ocupada</span>
                        <span style="display:flex;align-items:center;gap:6px;"><span style="width:12px;height:12px;border-radius:50%;background:var(--warning);display:inline-block;"></span> Limpieza</span>
                        <span style="display:flex;align-items:center;gap:6px;"><span style="width:12px;height:12px;border-radius:50%;background:var(--gray-400);display:inline-block;"></span> Mantenimiento</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal cambiar estado -->
<div id="modalEstado" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--white);border-radius:var(--radius-md);padding:2rem;width:100%;max-width:380px;margin:1rem;">
        <h3 style="margin-bottom:1.5rem;font-family:'Poppins',sans-serif;">Cambiar estado</h3>
        <div style="display:flex;flex-direction:column;gap:0.75rem;">
            <button onclick="confirmarEstado('libre')"         class="btn btn-outline" style="border-color:var(--success);color:var(--success);">✅ Libre</button>
            <button onclick="confirmarEstado('ocupada')"       class="btn btn-outline" style="border-color:var(--danger);color:var(--danger);">🔴 Ocupada</button>
            <button onclick="confirmarEstado('limpieza')"      class="btn btn-outline" style="border-color:var(--warning);color:var(--warning);">🧹 Limpieza</button>
            <button onclick="confirmarEstado('mantenimiento')" class="btn btn-outline">🔧 Mantenimiento</button>
            <button onclick="document.getElementById('modalEstado').style.display='none'" class="btn btn-outline">Cancelar</button>
        </div>
    </div>
</div>

<script>
let habSeleccionada = null;
function cambiarEstado(id, estadoActual) {
    habSeleccionada = id;
    document.getElementById('modalEstado').style.display = 'flex';
}
function confirmarEstado(nuevoEstado) {
    if (habSeleccionada) {
        window.location.href = `<?= SITE_URL ?>/pages/empleado/habitaciones.php?id=${habSeleccionada}&estado=${nuevoEstado}`;
    }
}
</script>
</body>
</html>
