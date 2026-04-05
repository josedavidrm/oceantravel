<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('empleado');

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("SELECT id_hotel FROM empleados_hotel WHERE id_usuario = ? AND activo = 1 LIMIT 1");
$stmt->execute([$_SESSION['id_usuario']]);
$asignacion = $stmt->fetch();
if (!$asignacion) { header('Location: ' . SITE_URL . '/pages/acceso-denegado.php'); exit; }

$idHotel = $asignacion['id_hotel'];
$error   = '';
$success = '';

// Crear incidencia
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("
        INSERT INTO incidencias (id_hotel, id_habitacion, id_usuario, tipo, descripcion, prioridad, estado)
        VALUES (?, ?, ?, ?, ?, ?, 'abierta')
    ");
    $stmt->execute([
        $idHotel,
        !empty($_POST['id_habitacion']) ? (int)$_POST['id_habitacion'] : null,
        $_SESSION['id_usuario'],
        $_POST['tipo']        ?? 'otro',
        $_POST['descripcion'] ?? '',
        $_POST['prioridad']   ?? 'media',
    ]);
    $success = '✅ Incidencia reportada exitosamente.';
}

// Obtener habitaciones del hotel
$habitaciones = $db->prepare("SELECT id_habitacion, numero, piso FROM habitaciones WHERE id_hotel = ? ORDER BY piso, numero");
$habitaciones->execute([$idHotel]);
$habitaciones = $habitaciones->fetchAll();

// Obtener incidencias del hotel
$stmt = $db->prepare("
    SELECT i.*, hab.numero AS num_hab, u.nombre AS empleado
    FROM incidencias i
    LEFT JOIN habitaciones hab ON hab.id_habitacion = i.id_habitacion
    INNER JOIN usuarios u ON u.id_usuario = i.id_usuario
    WHERE i.id_hotel = ?
    ORDER BY i.fecha_reporte DESC
    LIMIT 20
");
$stmt->execute([$idHotel]);
$incidencias = $stmt->fetchAll();

$prioridadBadge = ['baja' => 'badge-info', 'media' => 'badge-warning', 'alta' => 'badge-danger'];
$estadoBadge    = ['abierta' => 'badge-danger', 'en_proceso' => 'badge-warning', 'resuelta' => 'badge-success'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Incidencias — Panel Empleado</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar-empleado.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Reportar incidencia</span>
        </div>
        <div class="admin-content">

            <?php if ($error):   ?><div class="alert alert-danger"> <?= htmlspecialchars($error)   ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

                <!-- Formulario nueva incidencia -->
                <div class="card">
                    <div class="card-header"><h3>Nueva incidencia</h3></div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="form-group">
                                <label>Habitación afectada (opcional)</label>
                                <select name="id_habitacion" class="form-control">
                                    <option value="">— Área general del hotel —</option>
                                    <?php foreach ($habitaciones as $h): ?>
                                        <option value="<?= $h['id_habitacion'] ?>">Hab. <?= htmlspecialchars($h['numero']) ?> (Piso <?= $h['piso'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tipo de incidencia</label>
                                <select name="tipo" class="form-control">
                                    <option value="mantenimiento">🔧 Mantenimiento</option>
                                    <option value="limpieza">🧹 Limpieza</option>
                                    <option value="queja_cliente">😤 Queja de cliente</option>
                                    <option value="otro">📋 Otro</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Prioridad</label>
                                <select name="prioridad" class="form-control">
                                    <option value="baja">🟢 Baja</option>
                                    <option value="media" selected>🟡 Media</option>
                                    <option value="alta">🔴 Alta</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Descripción *</label>
                                <textarea name="descripcion" class="form-control" rows="4"
                                          placeholder="Describe el problema con detalle..." required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                📋 Reportar incidencia
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Incidencias recientes -->
                <div class="card">
                    <div class="card-header"><h3>Incidencias recientes</h3></div>
                    <div class="card-body" style="padding:0;">
                        <?php if (empty($incidencias)): ?>
                        <p style="text-align:center;padding:2rem;color:var(--text-secondary);">No hay incidencias registradas.</p>
                        <?php else: ?>
                        <?php foreach ($incidencias as $inc): ?>
                        <div style="padding:1rem;border-bottom:1px solid var(--gray-200);">
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.4rem;">
                                <div style="display:flex;gap:6px;">
                                    <span class="badge <?= $prioridadBadge[$inc['prioridad']] ?>"><?= ucfirst($inc['prioridad']) ?></span>
                                    <span class="badge <?= $estadoBadge[$inc['estado']] ?>"><?= ucfirst(str_replace('_',' ',$inc['estado'])) ?></span>
                                </div>
                                <small style="color:var(--text-secondary);"><?= date('d/m/Y H:i', strtotime($inc['fecha_reporte'])) ?></small>
                            </div>
                            <p style="font-size:0.85rem;margin-bottom:0.25rem;">
                                <?php if ($inc['num_hab']): ?><strong>Hab. <?= htmlspecialchars($inc['num_hab']) ?></strong> — <?php endif; ?>
                                <?= ucfirst($inc['tipo']) ?>
                            </p>
                            <p style="font-size:0.82rem;color:var(--text-secondary);"><?= htmlspecialchars(substr($inc['descripcion'], 0, 80)) ?>...</p>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</body>
</html>
