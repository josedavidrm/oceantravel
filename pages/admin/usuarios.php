<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('admin');

$usuarioObj = new Usuario();
$db = Database::getInstance()->getConnection();

// Cambiar estado usuario
if (isset($_GET['toggle'])) {
    $id    = (int)$_GET['toggle'];
    $est   = $_GET['est'] ?? 'activo';
    $nuevo = $est === 'activo' ? 'inactivo' : 'activo';
    $usuarioObj->cambiarEstado($id, $nuevo);
    header('Location: ' . SITE_URL . '/pages/admin/usuarios.php?updated=1');
    exit;
}

$rol    = $_GET['rol']    ?? '';
$buscar = $_GET['buscar'] ?? '';

$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];
if ($rol)    { $sql .= " AND rol = ?";                        $params[] = $rol; }
if ($buscar) { $sql .= " AND (nombre LIKE ? OR email LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
$sql .= " ORDER BY fecha_registro DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Obtener hoteles para el select
$hoteles = $db->query("SELECT id_hotel, nombre, sector FROM hoteles WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios — OceanTravel Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Gestión de usuarios</span>
        </div>
        <div class="admin-content">

            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">✅ Usuario actualizado correctamente.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">❌ Error al crear el usuario. El email puede estar en uso.</div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="card" style="margin-bottom:1.5rem;">
                <div class="card-body">
                    <form method="GET" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;">
                        <div class="form-group" style="margin:0;flex:1;min-width:200px;">
                            <label>Buscar</label>
                            <input type="text" name="buscar" class="form-control"
                                   placeholder="Nombre o email..."
                                   value="<?= htmlspecialchars($buscar) ?>">
                        </div>
                        <div class="form-group" style="margin:0;">
                            <label>Rol</label>
                            <select name="rol" class="form-control">
                                <option value="">Todos</option>
                                <option value="cliente"  <?= $rol === 'cliente'  ? 'selected' : '' ?>>Clientes</option>
                                <option value="empleado" <?= $rol === 'empleado' ? 'selected' : '' ?>>Empleados</option>
                                <option value="admin"    <?= $rol === 'admin'    ? 'selected' : '' ?>>Administradores</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">🔍 Filtrar</button>
                        <a href="<?= SITE_URL ?>/pages/admin/usuarios.php" class="btn btn-outline">Limpiar</a>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Usuarios registrados (<?= count($usuarios) ?>)</h3>
                    <button class="btn btn-sm btn-primary"
                            onclick="document.getElementById('modalNuevoUsuario').style.display='flex'">
                        + Nuevo usuario
                    </button>
                </div>
                <div class="card-body" style="padding:0;">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Rol</th>
                                    <th>Registro</th>
                                    <th>Último acceso</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $u): ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--ocean-bright),var(--ocean-light));display:flex;align-items:center;justify-content:center;color:white;font-size:0.8rem;font-weight:600;flex-shrink:0;">
                                                <?= strtoupper(substr($u['nombre'],0,1).substr($u['apellido'],0,1)) ?>
                                            </div>
                                            <strong><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></strong>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($u['email']) ?></td>
                                    <td><?= htmlspecialchars($u['telefono'] ?? '—') ?></td>
                                    <td>
                                        <span class="badge <?= $u['rol'] === 'admin' ? 'badge-gold' : ($u['rol'] === 'empleado' ? 'badge-info' : 'badge-success') ?>">
                                            <?= ucfirst($u['rol']) ?>
                                        </span>
                                    </td>
                                    <td style="font-size:0.82rem;"><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
                                    <td style="font-size:0.82rem;"><?= $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : '—' ?></td>
                                    <td>
                                        <span class="badge <?= $u['estado'] === 'activo' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= ucfirst($u['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($u['id_usuario'] !== $_SESSION['id_usuario']): ?>
                                        <a href="?toggle=<?= $u['id_usuario'] ?>&est=<?= $u['estado'] ?>"
                                           class="btn btn-sm <?= $u['estado'] === 'activo' ? 'btn-danger' : 'btn-outline' ?>"
                                           onclick="return confirm('¿Cambiar estado de este usuario?')">
                                            <?= $u['estado'] === 'activo' ? '🚫 Desactivar' : '✅ Activar' ?>
                                        </a>
                                        <?php else: ?>
                                        <span style="font-size:0.78rem;color:var(--text-secondary);">Tú mismo</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal nuevo usuario -->
<div id="modalNuevoUsuario" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;overflow-y:auto;padding:1rem;">
    <div style="background:var(--white);border-radius:var(--radius-md);padding:2rem;width:100%;max-width:480px;margin:auto;">
        <h3 style="margin-bottom:1.5rem;font-family:'Poppins',sans-serif;">Nuevo usuario</h3>
        <form method="POST" action="<?= SITE_URL ?>/pages/admin/ajax/crear-usuario.php">
            <div class="form-row">
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" name="apellido" class="form-control" required>
                </div>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Teléfono</label>
                <input type="tel" name="telefono" class="form-control" placeholder="+58 424-0000000">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="contrasena" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Rol</label>
                    <select name="rol" class="form-control" id="selectRol" onchange="toggleCampoHotel(this.value)">
                        <option value="cliente">Cliente</option>
                        <option value="empleado">Empleado</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
            </div>

            <!-- Campo hotel — solo visible cuando rol = empleado -->
            <div id="campoHotel" style="display:none;">
                <div style="background:var(--ocean-pale);border-radius:var(--radius-sm);padding:1rem;margin-bottom:1rem;border:1px solid var(--color-border-tertiary);">
                    <p style="font-size:0.8rem;color:var(--ocean-bright);font-weight:500;margin-bottom:0.75rem;">
                        🏨 Asignación de hotel
                    </p>
                    <div class="form-group" style="margin-bottom:0.75rem;">
                        <label>Hotel asignado *</label>
                        <select name="id_hotel" class="form-control" id="selectHotel">
                            <option value="">— Seleccionar hotel —</option>
                            <?php foreach ($hoteles as $h): ?>
                                <option value="<?= $h['id_hotel'] ?>">
                                    <?= htmlspecialchars($h['nombre']) ?>
                                    <?= $h['sector'] ? '— ' . htmlspecialchars($h['sector']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label>Cargo</label>
                        <input type="text" name="cargo" class="form-control"
                               placeholder="Ej: Recepcionista, Supervisor..." value="Recepcionista">
                    </div>
                </div>
            </div>

            <div style="display:flex;gap:1rem;margin-top:0.5rem;">
                <button type="submit" class="btn btn-primary btn-block">Crear usuario</button>
                <button type="button" class="btn btn-outline btn-block"
                        onclick="document.getElementById('modalNuevoUsuario').style.display='none'">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleCampoHotel(rol) {
    const campo = document.getElementById('campoHotel');
    const selectHotel = document.getElementById('selectHotel');
    if (rol === 'empleado') {
        campo.style.display = 'block';
        selectHotel.required = true;
    } else {
        campo.style.display = 'none';
        selectHotel.required = false;
    }
}
</script>
</body>
</html>