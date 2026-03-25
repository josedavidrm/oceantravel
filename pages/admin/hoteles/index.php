<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('admin');

$hotelObj = new Hotel();
$db       = Database::getInstance()->getConnection();

// Eliminar hotel
if (isset($_GET['eliminar'])) {
    $hotelObj->delete((int)$_GET['eliminar']);
    header('Location: ' . SITE_URL . '/pages/admin/hoteles/index.php?deleted=1');
    exit;
}

// Obtener todos los hoteles (activos e inactivos)
$stmt = $db->query("
    SELECT h.*, COUNT(DISTINCT hab.id_habitacion) AS total_habitaciones,
           COUNT(DISTINCT r.id_resena) AS total_resenas,
           COALESCE(AVG(r.puntuacion), 0) AS puntuacion_promedio
    FROM hoteles h
    LEFT JOIN habitaciones hab ON hab.id_hotel = h.id_hotel
    LEFT JOIN resenas r ON r.id_hotel = h.id_hotel AND r.visible = 1
    GROUP BY h.id_hotel
    ORDER BY h.fecha_registro DESC
");
$hoteles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoteles — OceanTravel Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Hoteles registrados</span>
            <div class="topbar-actions">
                <a href="<?= SITE_URL ?>/pages/admin/hoteles/crear.php" class="btn btn-sm btn-primary">
                    + Agregar hotel
                </a>
            </div>
        </div>

        <div class="admin-content">

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Hotel eliminado correctamente.</div>
            <?php endif; ?>
            <?php if (isset($_GET['created'])): ?>
                <div class="alert alert-success">✅ Hotel creado exitosamente.</div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body" style="padding:0;">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Hotel</th>
                                    <th>Sector</th>
                                    <th>Estrellas</th>
                                    <th>Habitaciones</th>
                                    <th>Valoración</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($hoteles)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:3rem;color:var(--text-secondary);">
                                        No hay hoteles registrados.
                                        <a href="<?= SITE_URL ?>/pages/admin/hoteles/crear.php" style="color:var(--ocean-bright);">
                                            Agregar el primero
                                        </a>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($hoteles as $h): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($h['nombre']) ?></strong><br>
                                        <small style="color:var(--text-secondary);">
                                            <?= htmlspecialchars($h['telefono'] ?? '—') ?>
                                        </small>
                                    </td>
                                    <td><?= htmlspecialchars($h['sector'] ?? '—') ?></td>
                                    <td style="color:var(--gold);"><?= str_repeat('★', $h['estrellas']) ?></td>
                                    <td><?= $h['total_habitaciones'] ?> hab.</td>
                                    <td>
                                        <span style="color:var(--gold);">★</span>
                                        <?= number_format($h['puntuacion_promedio'], 1) ?>
                                        <small style="color:var(--text-secondary);">(<?= $h['total_resenas'] ?>)</small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $h['estado'] === 'activo' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= ucfirst($h['estado']) ?>
                                        </span>
                                    </td>
                                    <td style="display:flex;gap:6px;flex-wrap:wrap;">
                                        <a href="<?= SITE_URL ?>/pages/admin/hoteles/editar.php?id=<?= $h['id_hotel'] ?>" class="btn btn-sm btn-outline">
                                            ✏️ Editar
                                        </a>
                                        <a href="<?= SITE_URL ?>/pages/admin/hoteles/fotos.php?id=<?= $h['id_hotel'] ?>" class="btn btn-sm btn-gold">
                                            🖼️ Fotos
                                        </a>
                                        <a href="<?= SITE_URL ?>/pages/admin/habitaciones.php?hotel=<?= $h['id_hotel'] ?>" class="btn btn-sm btn-outline">
                                            🛏️ Hab.
                                        </a>
                                        <a href="?eliminar=<?= $h['id_hotel'] ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('¿Eliminar este hotel? Esta acción no se puede deshacer.')">
                                            🗑️
                                        </a>
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
