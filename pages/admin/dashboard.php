<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('admin');

$reservaObj = new Reserva();
$hotelObj   = new Hotel();
$usuarioObj = new Usuario();

$stats    = $reservaObj->getEstadisticas();
$hoteles  = $hotelObj->listarActivos();
$usuarios = $usuarioObj->listarTodos();

$totalHoteles  = count($hoteles);
$totalUsuarios = count($usuarios);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — OceanTravel Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>

<div class="admin-layout">

    <!-- ── Sidebar ── -->
    <?php include __DIR__ . '/includes/sidebar.php'; ?>

    <!-- ── Contenido principal ── -->
    <div class="admin-main">

        <!-- Topbar -->
        <div class="admin-topbar">
            <span class="topbar-title">Dashboard</span>
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

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon blue">🏨</div>
                    <div class="stat-info">
                        <strong><?= $totalHoteles ?></strong>
                        <span>Hoteles activos</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon gold">📋</div>
                    <div class="stat-info">
                        <strong><?= $stats['total_reservas'] ?? 0 ?></strong>
                        <span>Reservas totales</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">💰</div>
                    <div class="stat-info">
                        <strong>$<?= number_format($stats['ingresos_mes'] ?? 0, 2) ?></strong>
                        <span>Ingresos este mes</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">👤</div>
                    <div class="stat-info">
                        <strong><?= $totalUsuarios ?></strong>
                        <span>Usuarios registrados</span>
                    </div>
                </div>
            </div>

            <!-- Fila de tarjetas -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">

                <!-- Reservas por estado -->
                <div class="card">
                    <div class="card-header">
                        <h3>Reservas por estado</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $estados = [
                            'confirmada' => ['label' => 'Confirmadas', 'class' => 'badge-success'],
                            'pendiente'  => ['label' => 'Pendientes',  'class' => 'badge-warning'],
                            'cancelada'  => ['label' => 'Canceladas',  'class' => 'badge-danger'],
                            'completada' => ['label' => 'Completadas', 'class' => 'badge-info'],
                        ];
                        foreach ($estados as $key => $e): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0;border-bottom:1px solid var(--gray-200);">
                            <span style="font-size:0.88rem;"><?= $e['label'] ?></span>
                            <span class="badge <?= $e['class'] ?>"><?= $stats[$key . 's'] ?? 0 ?></span>
                        </div>
                        <?php endforeach; ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0;">
                            <strong style="font-size:0.88rem;">Total ingresos</strong>
                            <strong style="color:var(--success);">$<?= number_format($stats['ingresos_totales'] ?? 0, 2) ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Acciones rápidas -->
                <div class="card">
                    <div class="card-header">
                        <h3>Acciones rápidas</h3>
                    </div>
                    <div class="card-body" style="display:flex;flex-direction:column;gap:0.75rem;">
                        <a href="<?= SITE_URL ?>/pages/admin/hoteles/crear.php" class="btn btn-primary btn-block">
                            🏨 Agregar nuevo hotel
                        </a>
                        <a href="<?= SITE_URL ?>/pages/admin/promociones/crear.php" class="btn btn-gold btn-block">
                            🏷️ Crear promoción
                        </a>
                        <a href="<?= SITE_URL ?>/pages/admin/reservas.php" class="btn btn-outline btn-block">
                            📋 Ver todas las reservas
                        </a>
                        <a href="<?= SITE_URL ?>/pages/admin/usuarios.php" class="btn btn-outline btn-block">
                            👥 Gestionar usuarios
                        </a>
                    </div>
                </div>

            </div>

            <!-- Hoteles registrados -->
            <div class="card">
                <div class="card-header">
                    <h3>Hoteles registrados</h3>
                    <a href="<?= SITE_URL ?>/pages/admin/hoteles/crear.php" class="btn btn-sm btn-primary">
                        + Agregar hotel
                    </a>
                </div>
                <div class="card-body" style="padding:0;">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Hotel</th>
                                    <th>Sector</th>
                                    <th>Estrellas</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($hoteles)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center;color:var(--text-secondary);padding:2rem;">
                                        No hay hoteles registrados aún.
                                        <a href="<?= SITE_URL ?>/pages/admin/hoteles/crear.php">Agregar el primero</a>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($hoteles as $h): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($h['nombre']) ?></strong><br>
                                        <small style="color:var(--text-secondary);"><?= htmlspecialchars($h['direccion']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($h['sector'] ?? '—') ?></td>
                                    <td><?= str_repeat('★', $h['estrellas']) ?></td>
                                    <td>
                                        <span class="badge <?= $h['estado'] === 'activo' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= ucfirst($h['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= SITE_URL ?>/pages/admin/hoteles/editar.php?id=<?= $h['id_hotel'] ?>" class="btn btn-sm btn-outline">
                                            ✏️ Editar
                                        </a>
                                        <a href="<?= SITE_URL ?>/pages/admin/hoteles/fotos.php?id=<?= $h['id_hotel'] ?>" class="btn btn-sm btn-gold">
                                            🖼️ Fotos
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
