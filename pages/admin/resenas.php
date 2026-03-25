<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('admin');

$resenaObj = new Resena();
$db = Database::getInstance()->getConnection();

// Toggle visibilidad
if (isset($_GET['toggle'])) {
    $resenaObj->toggleVisibilidad((int)$_GET['toggle']);
    header('Location: ' . SITE_URL . '/pages/admin/resenas.php?updated=1');
    exit;
}

// Responder reseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder'])) {
    $resenaObj->responderResena((int)$_POST['id_resena'], $_POST['respuesta']);
    header('Location: ' . SITE_URL . '/pages/admin/resenas.php?updated=1');
    exit;
}

$stmt = $db->query("
    SELECT r.*, u.nombre, u.apellido, h.nombre AS hotel_nombre
    FROM resenas r
    INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
    INNER JOIN hoteles h ON h.id_hotel = r.id_hotel
    ORDER BY r.fecha_resena DESC
");
$resenas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseñas — OceanTravel Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Reseñas y valoraciones</span>
        </div>
        <div class="admin-content">

            <?php if (isset($_GET['updated'])): ?><div class="alert alert-success">✅ Reseña actualizada.</div><?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Reseñas (<?= count($resenas) ?>)</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($resenas)): ?>
                        <p style="text-align:center;color:var(--text-secondary);padding:2rem;">
                            No hay reseñas todavía.
                        </p>
                    <?php else: ?>
                    <?php foreach ($resenas as $r): ?>
                    <div style="border:1px solid var(--gray-200);border-radius:var(--radius-md);padding:1.25rem;margin-bottom:1rem;<?= !$r['visible'] ? 'opacity:0.5;' : '' ?>">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
                            <div>
                                <div style="display:flex;align-items:center;gap:10px;margin-bottom:0.5rem;">
                                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--ocean-bright),var(--ocean-light));display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:0.85rem;">
                                        <?= strtoupper(substr($r['nombre'],0,1)) ?>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></strong>
                                        <span style="font-size:0.78rem;color:var(--text-secondary);margin-left:8px;">
                                            <?= date('d/m/Y', strtotime($r['fecha_resena'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <div style="color:var(--gold);margin-bottom:0.4rem;">
                                    <?= str_repeat('★', $r['puntuacion']) ?><?= str_repeat('☆', 5 - $r['puntuacion']) ?>
                                    <span style="color:var(--text-secondary);font-size:0.82rem;margin-left:6px;">
                                        🏨 <?= htmlspecialchars($r['hotel_nombre']) ?>
                                    </span>
                                </div>
                                <?php if ($r['titulo']): ?>
                                    <strong style="font-size:0.9rem;"><?= htmlspecialchars($r['titulo']) ?></strong>
                                <?php endif; ?>
                                <p style="font-size:0.88rem;color:var(--text-secondary);margin-top:0.3rem;">
                                    <?= htmlspecialchars($r['comentario']) ?>
                                </p>
                                <?php if ($r['respuesta_hotel']): ?>
                                <div style="background:var(--ocean-pale);border-left:3px solid var(--ocean-bright);padding:0.75rem;border-radius:0 var(--radius-sm) var(--radius-sm) 0;margin-top:0.75rem;font-size:0.85rem;">
                                    <strong style="color:var(--ocean-bright);">Respuesta OceanTravel:</strong>
                                    <p style="margin-top:0.25rem;color:var(--text-secondary);"><?= htmlspecialchars($r['respuesta_hotel']) ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex;gap:8px;flex-shrink:0;">
                                <button class="btn btn-sm btn-outline"
                                        onclick="responder(<?= $r['id_resena'] ?>)">
                                    💬 Responder
                                </button>
                                <a href="?toggle=<?= $r['id_resena'] ?>"
                                   class="btn btn-sm <?= $r['visible'] ? 'btn-danger' : 'btn-outline' ?>">
                                    <?= $r['visible'] ? '🙈 Ocultar' : '👁️ Mostrar' ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal responder -->
<div id="modalResponder" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:var(--white);border-radius:var(--radius-md);padding:2rem;width:100%;max-width:480px;margin:1rem;">
        <h3 style="margin-bottom:1.5rem;font-family:'Poppins',sans-serif;">Responder reseña</h3>
        <form method="POST">
            <input type="hidden" name="responder" value="1">
            <input type="hidden" name="id_resena" id="modalIdResena">
            <div class="form-group">
                <label>Tu respuesta</label>
                <textarea name="respuesta" class="form-control" rows="4"
                          placeholder="Escribe una respuesta profesional y cordial..." required></textarea>
            </div>
            <div style="display:flex;gap:1rem;margin-top:1rem;">
                <button type="submit" class="btn btn-primary btn-block">Publicar respuesta</button>
                <button type="button" class="btn btn-outline btn-block"
                        onclick="document.getElementById('modalResponder').style.display='none'">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<script>
function responder(id) {
    document.getElementById('modalIdResena').value = id;
    document.getElementById('modalResponder').style.display = 'flex';
}
</script>
</body>
</html>
