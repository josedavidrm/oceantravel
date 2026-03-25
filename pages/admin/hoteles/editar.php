<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('admin');

$hotelObj = new Hotel();
$db       = Database::getInstance()->getConnection();
$id       = (int)($_GET['id'] ?? 0);

$hotel = $hotelObj->getById($id);
if (!$hotel) {
    header('Location: ' . SITE_URL . '/pages/admin/hoteles/index.php');
    exit;
}

$servicios         = $db->query("SELECT * FROM servicios ORDER BY categoria, nombre")->fetchAll();
$serviciosHotel    = array_column($hotelObj->getServicios($id), 'id_servicio');
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $hotelObj->actualizar($id, [
        'nombre'         => $_POST['nombre']         ?? '',
        'descripcion'    => $_POST['descripcion']    ?? '',
        'sector'         => $_POST['sector']         ?? '',
        'direccion'      => $_POST['direccion']      ?? '',
        'telefono'       => $_POST['telefono']       ?? '',
        'email_contacto' => $_POST['email_contacto'] ?? '',
        'sitio_web'      => $_POST['sitio_web']      ?? '',
        'estrellas'      => $_POST['estrellas']      ?? 3,
        'estado'         => $_POST['estado']         ?? 'activo',
    ]);

    if ($result['success']) {
        // Actualizar servicios
        $hotelObj->actualizarServicios($id, $_POST['servicios'] ?? []);

        // Subir nuevas fotos si las hay
        if (!empty($_FILES['fotos']['name'][0])) {
            $hotelObj->subirMultiplesFotos($id, $_FILES['fotos']);
        }

        $success = 'Hotel actualizado exitosamente.';
        $hotel   = $hotelObj->getById($id);
        $serviciosHotel = array_column($hotelObj->getServicios($id), 'id_servicio');
    } else {
        $error = $result['message'];
    }
}

$fotos   = $hotelObj->getFotos($id);
$sectores = ['Playa El Agua','Porlamar','Pampatar','Juan Griego','La Asunción','Juangriego','El Yaque','Playa Parguito','Playa Caribe'];
$categorias = ['general' => 'General','habitacion' => 'Habitación','entretenimiento' => 'Entretenimiento','gastronomia' => 'Gastronomía','transporte' => 'Transporte'];
$serviciosPorCat = [];
foreach ($servicios as $s) $serviciosPorCat[$s['categoria']][] = $s;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar hotel — OceanTravel Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Editar: <?= htmlspecialchars($hotel['nombre']) ?></span>
            <div class="topbar-actions">
                <a href="<?= SITE_URL ?>/pages/hotel.php?id=<?= $id ?>" target="_blank" class="btn btn-sm btn-outline">
                    👁️ Ver en sitio
                </a>
                <a href="<?= SITE_URL ?>/pages/admin/hoteles/index.php" class="btn btn-sm btn-outline">← Volver</a>
            </div>
        </div>

        <div class="admin-content">

            <?php if ($error):   ?><div class="alert alert-danger"> <?= htmlspecialchars($error)   ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if (isset($_GET['created'])): ?><div class="alert alert-success">✅ Hotel creado. Ahora puedes editarlo y agregar más detalles.</div><?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">

                    <!-- Izquierda -->
                    <div style="display:flex;flex-direction:column;gap:1.5rem;">

                        <!-- Info general -->
                        <div class="card">
                            <div class="card-header"><h3>Información general</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Nombre del hotel *</label>
                                    <input type="text" name="nombre" class="form-control"
                                           value="<?= htmlspecialchars($hotel['nombre']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Descripción *</label>
                                    <textarea name="descripcion" class="form-control" rows="4" required><?= htmlspecialchars($hotel['descripcion']) ?></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Sector / Zona</label>
                                        <select name="sector" class="form-control">
                                            <option value="">Seleccionar sector</option>
                                            <?php foreach ($sectores as $s): ?>
                                                <option value="<?= $s ?>" <?= $hotel['sector'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Estrellas</label>
                                        <select name="estrellas" class="form-control">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <option value="<?= $i ?>" <?= $hotel['estrellas'] == $i ? 'selected' : '' ?>>
                                                    <?= str_repeat('★', $i) ?> (<?= $i ?>)
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Dirección completa *</label>
                                    <input type="text" name="direccion" class="form-control"
                                           value="<?= htmlspecialchars($hotel['direccion']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Contacto -->
                        <div class="card">
                            <div class="card-header"><h3>Contacto</h3></div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Teléfono</label>
                                        <input type="tel" name="telefono" class="form-control"
                                               value="<?= htmlspecialchars($hotel['telefono'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Email de contacto</label>
                                        <input type="email" name="email_contacto" class="form-control"
                                               value="<?= htmlspecialchars($hotel['email_contacto'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Sitio web</label>
                                    <input type="url" name="sitio_web" class="form-control"
                                           value="<?= htmlspecialchars($hotel['sitio_web'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Fotos actuales -->
                        <div class="card">
                            <div class="card-header">
                                <h3>Galería de fotos (<?= count($fotos) ?>)</h3>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($fotos)): ?>
                                <div class="photos-grid" style="margin-bottom:1.5rem;">
                                    <?php foreach ($fotos as $foto): ?>
                                    <div class="photo-thumb <?= $foto['es_portada'] ? 'is-portada' : '' ?>" id="foto-<?= $foto['id_foto'] ?>">
                                        <img src="<?= UPLOAD_URL ?>hoteles/<?= $id ?>/<?= htmlspecialchars($foto['url_foto']) ?>"
                                             alt="Foto hotel">
                                        <div class="photo-actions">
                                            <?php if (!$foto['es_portada']): ?>
                                            <button type="button" title="Hacer portada"
                                                    onclick="hacerPortada(<?= $foto['id_foto'] ?>, <?= $id ?>)"
                                                    style="background:var(--gold);color:var(--ocean-deep);">⭐</button>
                                            <?php endif; ?>
                                            <button type="button" title="Eliminar"
                                                    onclick="eliminarFoto(<?= $foto['id_foto'] ?>)"
                                                    style="background:var(--danger);color:white;">🗑️</button>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:0.75rem;">
                                    Agregar más fotos:
                                </p>
                                <div class="upload-zone" onclick="document.getElementById('fotosInput').click()">
                                    <div class="upload-icon">🖼️</div>
                                    <p>Clic aquí o arrastra las fotos</p>
                                    <span>JPG, PNG, WebP — Máx. 5MB</span>
                                </div>
                                <input type="file" id="fotosInput" name="fotos[]" multiple
                                       accept="image/jpeg,image/png,image/webp"
                                       style="display:none" onchange="previsualizarFotos(this)">
                                <div class="photos-grid" id="previewGrid"></div>
                            </div>
                        </div>

                    </div>

                    <!-- Derecha -->
                    <div style="display:flex;flex-direction:column;gap:1.5rem;">

                        <!-- Estado -->
                        <div class="card">
                            <div class="card-header"><h3>Estado</h3></div>
                            <div class="card-body">
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Estado del hotel</label>
                                    <select name="estado" class="form-control">
                                        <option value="activo"   <?= $hotel['estado'] === 'activo'   ? 'selected' : '' ?>>✅ Activo</option>
                                        <option value="inactivo" <?= $hotel['estado'] === 'inactivo' ? 'selected' : '' ?>>❌ Inactivo</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Servicios -->
                        <div class="card">
                            <div class="card-header"><h3>Servicios y amenidades</h3></div>
                            <div class="card-body">
                                <?php foreach ($categorias as $cat => $label):
                                    if (empty($serviciosPorCat[$cat])) continue; ?>
                                <p style="font-size:0.75rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.08em;margin:0.75rem 0 0.4rem;">
                                    <?= $label ?>
                                </p>
                                <?php foreach ($serviciosPorCat[$cat] as $s): ?>
                                <label style="display:flex;align-items:center;gap:8px;padding:0.3rem 0;font-size:0.88rem;cursor:pointer;">
                                    <input type="checkbox" name="servicios[]" value="<?= $s['id_servicio'] ?>"
                                           <?= in_array($s['id_servicio'], $serviciosHotel) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($s['nombre']) ?>
                                </label>
                                <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            💾 Guardar cambios
                        </button>

                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previsualizarFotos(input) {
    const grid = document.getElementById('previewGrid');
    grid.innerHTML = '';
    [...input.files].forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'photo-thumb';
            div.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            grid.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}

function eliminarFoto(idFoto) {
    if (!confirm('¿Eliminar esta foto?')) return;
    fetch('<?= SITE_URL ?>/pages/admin/ajax/fotos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'eliminar', id_foto: idFoto })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('foto-' + idFoto)?.remove();
        } else {
            alert('Error al eliminar la foto.');
        }
    });
}

function hacerPortada(idFoto, idHotel) {
    fetch('<?= SITE_URL ?>/pages/admin/ajax/fotos.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ accion: 'portada', id_foto: idFoto, id_hotel: idHotel })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) location.reload();
    });
}
</script>

</body>
</html>
