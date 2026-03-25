<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('admin');

$promoObj = new Promocion();
$hotelObj = new Hotel();
$hoteles  = $hotelObj->listarActivos();

// Toggle activo/inactivo
if (isset($_GET['toggle'])) {
    $promoObj->toggleEstado((int)$_GET['toggle']);
    header('Location: ' . SITE_URL . '/pages/admin/promociones/index.php?updated=1');
    exit;
}
// Eliminar
if (isset($_GET['eliminar'])) {
    $promoObj->delete((int)$_GET['eliminar']);
    header('Location: ' . SITE_URL . '/pages/admin/promociones/index.php?deleted=1');
    exit;
}

$promociones = $promoObj->listarTodas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promociones — OceanTravel Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Promociones y descuentos</span>
        </div>
        <div class="admin-content">

            <?php if (isset($_GET['updated'])): ?><div class="alert alert-success">✅ Promoción actualizada.</div><?php endif; ?>
            <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✅ Promoción eliminada.</div><?php endif; ?>
            <?php if (isset($_GET['created'])): ?><div class="alert alert-success">✅ Promoción creada exitosamente.</div><?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Promociones (<?= count($promociones) ?>)</h3>
                    <button class="btn btn-sm btn-primary"
                            onclick="document.getElementById('modalPromo').style.display='flex'">
                        + Nueva promoción
                    </button>
                </div>
                <div class="card-body" style="padding:0;">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Hotel</th>
                                    <th>Descuento</th>
                                    <th>Código</th>
                                    <th>Vigencia</th>
                                    <th>Usos</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($promociones)): ?>
                                <tr>
                                    <td colspan="8" style="text-align:center;padding:2rem;color:var(--text-secondary);">
                                        No hay promociones. ¡Crea la primera!
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($promociones as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($p['nombre']) ?></strong><br>
                                        <small style="color:var(--text-secondary);"><?= htmlspecialchars($p['descripcion'] ?? '') ?></small>
                                    </td>
                                    <td><?= $p['hotel_nombre'] ? htmlspecialchars($p['hotel_nombre']) : '<span style="color:var(--text-secondary);">Todos</span>' ?></td>
                                    <td>
                                        <span class="badge badge-gold">
                                            <?= $p['tipo_descuento'] === 'porcentaje' ? $p['valor_descuento'] . '%' : '$' . number_format($p['valor_descuento'], 2) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($p['codigo_promo']): ?>
                                        <code style="background:var(--gray-100);padding:0.2rem 0.5rem;border-radius:4px;font-size:0.82rem;">
                                            <?= htmlspecialchars($p['codigo_promo']) ?>
                                        </code>
                                        <?php else: ?>—<?php endif; ?>
                                    </td>
                                    <td style="font-size:0.82rem;">
                                        <?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?> —<br>
                                        <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?>
                                    </td>
                                    <td>
                                        <?= $p['usos_actuales'] ?>/<?= $p['usos_maximos'] ?? '∞' ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $p['activo'] ? 'badge-success' : 'badge-danger' ?>">
                                            <?= $p['activo'] ? 'Activa' : 'Inactiva' ?>
                                        </span>
                                    </td>
                                    <td style="display:flex;gap:6px;">
                                        <a href="?toggle=<?= $p['id_promocion'] ?>" class="btn btn-sm btn-outline">
                                            <?= $p['activo'] ? '⏸️' : '▶️' ?>
                                        </a>
                                        <a href="?eliminar=<?= $p['id_promocion'] ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('¿Eliminar esta promoción?')">🗑️</a>
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

<!-- Modal nueva promoción -->
<div id="modalPromo" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;overflow-y:auto;padding:1rem;">
    <div style="background:var(--white);border-radius:var(--radius-md);padding:2rem;width:100%;max-width:540px;">
        <h3 style="margin-bottom:1.5rem;font-family:'Poppins',sans-serif;">Nueva promoción</h3>
        <form method="POST" action="<?= SITE_URL ?>/pages/admin/ajax/crear-promo.php">
            <div class="form-group">
                <label>Nombre de la promoción *</label>
                <input type="text" name="nombre" class="form-control" placeholder="Ej: Descuento Semana Santa" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción breve de la oferta"></textarea>
            </div>
            <div class="form-group">
                <label>Hotel (dejar vacío para aplicar a todos)</label>
                <select name="id_hotel" class="form-control">
                    <option value="">Todos los hoteles</option>
                    <?php foreach ($hoteles as $h): ?>
                        <option value="<?= $h['id_hotel'] ?>"><?= htmlspecialchars($h['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Tipo de descuento</label>
                    <select name="tipo_descuento" class="form-control">
                        <option value="porcentaje">Porcentaje (%)</option>
                        <option value="monto_fijo">Monto fijo ($)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Valor del descuento *</label>
                    <input type="number" name="valor_descuento" class="form-control" min="1" step="0.01" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Código promocional</label>
                    <input type="text" name="codigo_promo" class="form-control" placeholder="Ej: VERANO25" style="text-transform:uppercase;">
                </div>
                <div class="form-group">
                    <label>Usos máximos</label>
                    <input type="number" name="usos_maximos" class="form-control" placeholder="Dejar vacío = ilimitado" min="1">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Fecha inicio *</label>
                    <input type="date" name="fecha_inicio" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Fecha fin *</label>
                    <input type="date" name="fecha_fin" class="form-control" required>
                </div>
            </div>
            <div style="display:flex;gap:1rem;margin-top:1rem;">
                <button type="submit" class="btn btn-primary btn-block">Crear promoción</button>
                <button type="button" class="btn btn-outline btn-block"
                        onclick="document.getElementById('modalPromo').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
