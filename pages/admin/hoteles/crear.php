<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('admin');

$hotelObj = new Hotel();
$db       = Database::getInstance()->getConnection();
$servicios = $db->query("SELECT * FROM servicios ORDER BY categoria, nombre")->fetchAll();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $hotelObj->crear([
        'nombre'         => $_POST['nombre']         ?? '',
        'descripcion'    => $_POST['descripcion']    ?? '',
        'ciudad'         => 'Isla de Margarita',
        'sector'         => $_POST['sector']         ?? '',
        'direccion'      => $_POST['direccion']      ?? '',
        'telefono'       => $_POST['telefono']       ?? '',
        'email_contacto' => $_POST['email_contacto'] ?? '',
        'sitio_web'      => $_POST['sitio_web']      ?? '',
        'estrellas'      => $_POST['estrellas']      ?? 3,
        'estado'         => $_POST['estado']         ?? 'activo',
    ]);

    if ($result['success']) {
        $idHotel = $result['id'];

        if (!empty($_POST['servicios'])) {
            $hotelObj->actualizarServicios($idHotel, $_POST['servicios']);
        }

        if (!empty($_FILES['fotos']['name'][0])) {
            $hotelObj->subirMultiplesFotos($idHotel, $_FILES['fotos']);
        }

        header('Location: ' . SITE_URL . '/pages/admin/hoteles/editar.php?id=' . $idHotel . '&created=1');
        exit;
    } else {
        $error = $result['message'];
    }
}

$sectores = ['Playa El Agua','Porlamar','Pampatar','Juan Griego','La Asunción','Juangriego','El Yaque','Playa Parguito','Playa Caribe'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar hotel — OceanTravel Admin</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>
<div class="admin-layout">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="admin-main">
        <div class="admin-topbar">
            <span class="topbar-title">Agregar nuevo hotel</span>
            <div class="topbar-actions">
                <a href="<?= SITE_URL ?>/pages/admin/hoteles/index.php" class="btn btn-sm btn-outline">
                    ← Volver
                </a>
            </div>
        </div>

        <div class="admin-content">

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">

                    <!-- Columna izquierda -->
                    <div style="display:flex;flex-direction:column;gap:1.5rem;">

                        <!-- Información general -->
                        <div class="card">
                            <div class="card-header"><h3>Información general</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Nombre del hotel *</label>
                                    <input type="text" name="nombre" class="form-control"
                                           placeholder="Ej: Hotel Playa Bella"
                                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Descripción *</label>
                                    <textarea name="descripcion" class="form-control" rows="4"
                                              placeholder="Describe el hotel, sus características principales..."
                                              required><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                                </div>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Sector / Zona</label>
                                        <select name="sector" class="form-control">
                                            <option value="">Seleccionar sector</option>
                                            <?php foreach ($sectores as $s): ?>
                                                <option value="<?= $s ?>" <?= ($_POST['sector'] ?? '') === $s ? 'selected' : '' ?>>
                                                    <?= $s ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Estrellas</label>
                                        <select name="estrellas" class="form-control">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <option value="<?= $i ?>" <?= ($_POST['estrellas'] ?? 3) == $i ? 'selected' : '' ?>>
                                                    <?= str_repeat('★', $i) ?> (<?= $i ?> estrella<?= $i > 1 ? 's' : '' ?>)
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Dirección completa *</label>
                                    <input type="text" name="direccion" class="form-control"
                                           placeholder="Av. Principal, Playa El Agua, Municipio..."
                                           value="<?= htmlspecialchars($_POST['direccion'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>

                        <!-- Contacto -->
                        <div class="card">
                            <div class="card-header"><h3>Información de contacto</h3></div>
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Teléfono</label>
                                        <input type="tel" name="telefono" class="form-control"
                                               placeholder="+58 295-000-0000"
                                               value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Email de contacto</label>
                                        <input type="email" name="email_contacto" class="form-control"
                                               placeholder="reservas@hotel.com"
                                               value="<?= htmlspecialchars($_POST['email_contacto'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Sitio web (opcional)</label>
                                    <input type="url" name="sitio_web" class="form-control"
                                           placeholder="https://www.mihotel.com"
                                           value="<?= htmlspecialchars($_POST['sitio_web'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Fotos -->
                        <div class="card">
                            <div class="card-header"><h3>Fotos del hotel</h3></div>
                            <div class="card-body">
                                <p style="font-size:0.85rem;color:var(--text-secondary);margin-bottom:1rem;">
                                    Puedes subir varias fotos a la vez. La primera foto será la portada.
                                    Formatos: JPG, PNG, WebP. Máximo 5MB por foto.
                                </p>
                                <div class="upload-zone" id="uploadZone" onclick="document.getElementById('fotosInput').click()">
                                    <div class="upload-icon">🖼️</div>
                                    <p>Haz clic aquí o arrastra las fotos</p>
                                    <span>JPG, PNG, WebP — Máx. 5MB por imagen</span>
                                </div>
                                <input type="file" id="fotosInput" name="fotos[]"
                                       multiple accept="image/jpeg,image/png,image/webp"
                                       style="display:none" onchange="previsualizarFotos(this)">
                                <div class="photos-grid" id="previewGrid"></div>
                            </div>
                        </div>

                    </div>

                    <!-- Columna derecha -->
                    <div style="display:flex;flex-direction:column;gap:1.5rem;">

                        <!-- Estado -->
                        <div class="card">
                            <div class="card-header"><h3>Estado</h3></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>Estado del hotel</label>
                                    <select name="estado" class="form-control">
                                        <option value="activo"   <?= ($_POST['estado'] ?? 'activo') === 'activo'   ? 'selected' : '' ?>>✅ Activo</option>
                                        <option value="inactivo" <?= ($_POST['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>❌ Inactivo</option>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-bottom:0;">
                                    <label>Ciudad</label>
                                    <input type="text" class="form-control" value="Isla de Margarita" readonly
                                           style="background:var(--gray-100);color:var(--text-secondary);">
                                </div>
                            </div>
                        </div>

                        <!-- Servicios y amenidades -->
                        <div class="card">
                            <div class="card-header"><h3>Servicios y amenidades</h3></div>
                            <div class="card-body">
                                <?php
                                $categorias = [
                                    'general'         => 'General',
                                    'habitacion'      => 'Habitación',
                                    'entretenimiento' => 'Entretenimiento',
                                    'gastronomia'     => 'Gastronomía',
                                    'transporte'      => 'Transporte',
                                ];
                                $serviciosPorCat = [];
                                foreach ($servicios as $s) {
                                    $serviciosPorCat[$s['categoria']][] = $s;
                                }
                                foreach ($categorias as $cat => $label):
                                    if (empty($serviciosPorCat[$cat])) continue;
                                ?>
                                <p style="font-size:0.75rem;font-weight:600;color:var(--text-secondary);text-transform:uppercase;letter-spacing:0.08em;margin:0.75rem 0 0.4rem;">
                                    <?= $label ?>
                                </p>
                                <?php foreach ($serviciosPorCat[$cat] as $s): ?>
                                <label style="display:flex;align-items:center;gap:8px;padding:0.3rem 0;font-size:0.88rem;cursor:pointer;">
                                    <input type="checkbox" name="servicios[]" value="<?= $s['id_servicio'] ?>"
                                           <?= in_array($s['id_servicio'], $_POST['servicios'] ?? []) ? 'checked' : '' ?>>
                                    <?= htmlspecialchars($s['nombre']) ?>
                                </label>
                                <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Botón guardar -->
                        <button type="submit" class="btn btn-primary btn-block btn-lg">
                            🏨 Guardar hotel
                        </button>
                        <a href="<?= SITE_URL ?>/pages/admin/hoteles/index.php" class="btn btn-outline btn-block">
                            Cancelar
                        </a>

                    </div>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
const uploadZone = document.getElementById('uploadZone');
uploadZone.addEventListener('dragover',  e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('drag-over'));
uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    const input = document.getElementById('fotosInput');
    const dt    = new DataTransfer();
    [...e.dataTransfer.files].forEach(f => dt.items.add(f));
    input.files = dt.files;
    previsualizarFotos(input);
});

function previsualizarFotos(input) {
    const grid = document.getElementById('previewGrid');
    grid.innerHTML = '';
    [...input.files].forEach((file, i) => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.className = 'photo-thumb' + (i === 0 ? ' is-portada' : '');
            div.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            grid.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
}
</script>

</body>
</html>