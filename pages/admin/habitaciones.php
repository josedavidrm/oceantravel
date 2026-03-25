<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('admin');

$habitacionObj = new Habitacion();
$hotelObj      = new Hotel();
$db            = Database::getInstance()->getConnection();

// Hotel seleccionado
$idHotel = (int)($_GET['hotel'] ?? 0);

// Cambiar estado habitación
if (isset($_GET['estado']) && isset($_GET['id'])) {
    $habitacionObj->cambiarEstado((int)$_GET['id'], $_GET['estado']);
    header('Location: ' . SITE_URL . '/pages/admin/habitaciones.php?hotel=' . $idHotel . '&updated=1');
    exit;
}

// Eliminar habitación
if (isset($_GET['eliminar'])) {
    $habitacionObj->delete((int)$_GET['eliminar']);
    header('Location: ' . SITE_URL . '/pages/admin/habitaciones.php?hotel=' . $idHotel . '&updated=1');
    exit;
}

// Crear tipo de habitación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_tipo'])) {
    $habitacionObj->crearTipo([
        'id_hotel'    => $_POST['id_hotel'],
        'nombre'      => $_POST['nombre'],
        'descripcion' => $_POST['descripcion'] ?? '',
        'capacidad'   => $_POST['capacidad'] ?? 2,
        'precio_base' => $_POST['precio_base'],
    ]);
    header('Location: ' . SITE_URL . '/pages/admin/habitaciones.php?hotel=' . $_POST['id_hotel'] . '&updated=1');
    exit;
}

// Crear habitación física
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_habitacion'])) {
    $habitacionObj->crear([
        'id_hotel' => $_POST['id_hotel'],
        'id_tipo'  => $_POST['id_tipo'],
        'numero'   => $_POST['numero'],
        'piso'     => $_POST['piso'] ?? 1,
    ]);
    header('Location: ' . SITE_URL . '/pages/admin/habitaciones.php?hotel=' . $_POST['id_hotel'] . '&updated=1');
    exit;
}

$hoteles = $hotelObj->listarActivos();

$habitaciones = [];
$tipos        = [];
$resumen      = [];
$hotelActual  = null;

if ($idHotel) {
    $habitaciones = $habitacionObj->listarPorHotel($idHotel);
    $resumen      = $habitacionObj->getResumenEstados($idHotel);
    $hotelActual  = $hotelObj->getById($idHotel);
    $stmt = $db->prepare("SELECT * FROM tipo_habitacion WHERE id_hotel = ? AND activo = 1 ORDER BY precio_base");
    $stmt->execute([$idHotel]);
    $tipos = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habitaciones — OceanTravel Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Gestión de habitaciones</span>
        </div>
        <div class="admin-content">

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">✅ Cambios guardados.</div>
            <?php endif; ?>

            <!-- Selector de hotel -->
            <div class="card" style="margin-bottom:1.5rem;">
                <div class="card-body">
                    <form method="GET" style="display:flex;gap:1rem;align-items:flex-end;">
                        <div class="form-group" style="margin:0;flex:1;">
                            <label>Selecciona un hotel</label>
                            <select name="hotel" class="form-control" onchange="this.form.submit()">
                                <option value="">— Seleccionar hotel —</option>
                                <?php foreach ($hoteles as $h): ?>
                                    <option value="<?= $h['id_hotel'] ?>" <?= $idHotel == $h['id_hotel'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($h['nombre']) ?> — <?= htmlspecialchars($h['sector'] ?? '') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($idHotel && $hotelActual): ?>

            <!-- Resumen estados -->
            <div class="stats-grid" style="margin-bottom:1.5rem;">
                <?php
                $estadosInfo = [
                    'libre'         => ['label' => 'Libres',        'icon' => '✅', 'class' => 'green'],
                    'ocupada'       => ['label' => 'Ocupadas',      'icon' => '🔴', 'class' => 'red'],
                    'limpieza'      => ['label' => 'En limpieza',   'icon' => '🧹', 'class' => 'amber'],
                    'mantenimiento' => ['label' => 'Mantenimiento', 'icon' => '🔧', 'class' => 'gray'],
                ];
                foreach ($estadosInfo as $key => $info): ?>
                <div class="stat-card">
                    <div class="stat-icon <?= $info['class'] ?>"><?= $info['icon'] ?></div>
                    <div class="stat-info">
                        <strong><?= $resumen[$key] ?? 0 ?></strong>
                        <span><?= $info['label'] ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">

                <!-- Tipos de habitación -->
                <div class="card">
                    <div class="card-header">
                        <h3>Tipos de habitación</h3>
                        <button class="btn btn-sm btn-primary"
                                onclick="document.getElementById('modalTipo').style.display='flex'">
                            + Nuevo tipo
                        </button>
                    </div>
                    <div class="card-body" style="padding:0;">
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Capacidad</th>
                                        <th>Precio/noche</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tipos)): ?>
                                    <tr>
                                        <td colspan="3" style="text-align:center;padding:1.5rem;color:var(--text-secondary);">
                                            No hay tipos. Agrega uno primero.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($tipos as $t): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($t['nombre']) ?></strong><br>
                                            <small style="color:var(--text-secondary);"><?= htmlspecialchars($t['descripcion'] ?? '') ?></small>
                                        </td>
                                        <td><?= $t['capacidad'] ?> personas</td>
                                        <td><strong style="color:var(--success);">$<?= number_format($t['precio_base'], 2) ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Agregar habitación física -->
                <div class="card">
                    <div class="card-header"><h3>Agregar habitación</h3></div>
                    <div class="card-body">
                        <?php if (empty($tipos)): ?>
                            <div class="alert alert-warning">Primero debes crear al menos un tipo de habitación.</div>
                        <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="crear_habitacion" value="1">
                            <input type="hidden" name="id_hotel" value="<?= $idHotel ?>">
                            <div class="form-group">
                                <label>Tipo de habitación</label>
                                <select name="id_tipo" class="form-control" required>
                                    <option value="">Seleccionar tipo</option>
                                    <?php foreach ($tipos as $t): ?>
                                        <option value="<?= $t['id_tipo'] ?>"><?= htmlspecialchars($t['nombre']) ?> — $<?= number_format($t['precio_base'], 2) ?>/noche</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Número de habitación</label>
                                    <input type="text" name="numero" class="form-control" placeholder="Ej: 101, PH1" required>
                                </div>
                                <div class="form-group">
                                    <label>Piso</label>
                                    <input type="number" name="piso" class="form-control" min="1" value="1" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">➕ Agregar habitación</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Listado de habitaciones -->
            <div class="card">
                <div class="card-header">
                    <h3>Habitaciones de <?= htmlspecialchars($hotelActual['nombre']) ?> (<?= count($habitaciones) ?>)</h3>
                </div>
                <div class="card-body" style="padding:0;">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>N°</th>
                                    <th>Piso</th>
                                    <th>Tipo</th>
                                    <th>Precio/noche</th>
                                    <th>Estado</th>
                                    <th>Cambiar estado</th>
                                    <th>Eliminar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($habitaciones)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center;padding:2rem;color:var(--text-secondary);">
                                        No hay habitaciones registradas.
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php
                                $estadosBadge = [
                                    'libre'         => 'badge-success',
                                    'ocupada'       => 'badge-danger',
                                    'limpieza'      => 'badge-warning',
                                    'mantenimiento' => 'badge-info',
                                ];
                                foreach ($habitaciones as $hab): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($hab['numero']) ?></strong></td>
                                    <td>Piso <?= $hab['piso'] ?></td>
                                    <td><?= htmlspecialchars($hab['tipo_nombre']) ?></td>
                                    <td>$<?= number_format($hab['precio_base'], 2) ?></td>
                                    <td>
                                        <span class="badge <?= $estadosBadge[$hab['estado']] ?>">
                                            <?= ucfirst($hab['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <select class="form-control" style="padding:0.3rem 0.5rem;font-size:0.82rem;width:auto;"
                                                onchange="cambiarEstado(<?= $hab['id_habitacion'] ?>, this.value)">
                                            <option value="libre"         <?= $hab['estado']==='libre'         ? 'selected':'' ?>>Libre</option>
                                            <option value="ocupada"       <?= $hab['estado']==='ocupada'       ? 'selected':'' ?>>Ocupada</option>
                                            <option value="limpieza"      <?= $hab['estado']==='limpieza'      ? 'selected':'' ?>>Limpieza</option>
                                            <option value="mantenimiento" <?= $hab['estado']==='mantenimiento' ? 'selected':'' ?>>Mantenimiento</option>
                                        </select>
                                    </td>
                                    <td>
                                        <a href="?hotel=<?= $idHotel ?>&eliminar=<?= $hab['id_habitacion'] ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('¿Eliminar habitación <?= $hab['numero'] ?>?')">
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

            <?php else: ?>
            <div class="alert alert-info">
                👆 Selecciona un hotel arriba para ver y gestionar sus habitaciones.
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Modal nuevo tipo de habitación -->
<div id="modalTipo" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--white);border-radius:var(--radius-md);padding:2rem;width:100%;max-width:480px;margin:1rem;">
        <h3 style="margin-bottom:1.5rem;font-family:'Poppins',sans-serif;">Nuevo tipo de habitación</h3>
        <form method="POST">
            <input type="hidden" name="crear_tipo" value="1">
            <input type="hidden" name="id_hotel" value="<?= $idHotel ?>">
            <div class="form-group">
                <label>Nombre del tipo *</label>
                <input type="text" name="nombre" class="form-control" placeholder="Ej: Suite Junior, Doble Vista al Mar" required>
            </div>
            <div class="form-group">
                <label>Descripción</label>
                <textarea name="descripcion" class="form-control" rows="2" placeholder="Descripción de este tipo de habitación"></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Capacidad (personas)</label>
                    <input type="number" name="capacidad" class="form-control" min="1" max="10" value="2" required>
                </div>
                <div class="form-group">
                    <label>Precio base/noche (USD)</label>
                    <input type="number" name="precio_base" class="form-control" min="1" step="0.01" placeholder="0.00" required>
                </div>
            </div>
            <div style="display:flex;gap:1rem;margin-top:1rem;">
                <button type="submit" class="btn btn-primary btn-block">Crear tipo</button>
                <button type="button" class="btn btn-outline btn-block"
                        onclick="document.getElementById('modalTipo').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function cambiarEstado(id, estado) {
    window.location.href = `<?= SITE_URL ?>/pages/admin/habitaciones.php?hotel=<?= $idHotel ?>&id=${id}&estado=${estado}`;
}
</script>
</body>
</html>