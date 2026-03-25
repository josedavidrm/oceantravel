<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/autoloader.php';

session_start();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL); exit; }

$hotelObj  = new Hotel();
$resenaObj = new Resena();

$hotel = $hotelObj->getDetalle($id);
if (!$hotel) { header('Location: ' . SITE_URL); exit; }

$resenas = $resenaObj->getResenasHotel($id, 6);

$entrada  = $_GET['entrada'] ?? '';
$salida   = $_GET['salida']  ?? '';
$huespedes = (int)($_GET['huespedes'] ?? 2);

$habitacionObj     = new Habitacion();
$habitacionesDisp  = [];
if ($entrada && $salida) {
    $habitacionesDisp = $habitacionObj->buscarDisponibles($id, $entrada, $salida, $huespedes);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($hotel['nombre']) ?> — OceanTravel</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
    <style>
        .hotel-hero { position:relative; height: 420px; overflow:hidden; background: var(--ocean-deep); }
        .hotel-hero img { width:100%; height:100%; object-fit:cover; opacity:0.85; }
        .hotel-hero-overlay { position:absolute; inset:0; background:linear-gradient(to top, rgba(10,22,40,0.9) 0%, transparent 60%); }
        .hotel-hero-content { position:absolute; bottom:2rem; left:0; right:0; }
        .gallery-grid { display:grid; grid-template-columns: 2fr 1fr 1fr; grid-template-rows: 200px 200px; gap:8px; border-radius: var(--radius-md); overflow:hidden; }
        .gallery-grid img { width:100%; height:100%; object-fit:cover; cursor:pointer; transition: opacity 0.2s; }
        .gallery-grid img:hover { opacity:0.85; }
        .gallery-grid .main-photo { grid-row: span 2; }
        .detail-layout { display:grid; grid-template-columns: 1fr 360px; gap:2rem; margin-top:2rem; }
        .booking-card { position:sticky; top:calc(var(--navbar-h) + 1rem); background:var(--white); border:1px solid var(--gray-200); border-radius:var(--radius-lg); padding:1.5rem; box-shadow:var(--shadow-md); }
        .hab-card { border:1.5px solid var(--gray-200); border-radius:var(--radius-md); padding:1.25rem; margin-bottom:1rem; transition: border-color 0.2s; }
        .hab-card:hover { border-color: var(--ocean-light); }
        .hab-card.selected { border-color: var(--ocean-bright); background: var(--ocean-pale); }
        .servicio-chip { display:inline-flex; align-items:center; gap:6px; padding:0.4rem 0.8rem; background:var(--gray-100); border-radius:50px; font-size:0.82rem; color:var(--text-secondary); margin:0.25rem; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar solid" id="navbar">
    <div class="container navbar-inner">
        <a href="<?= SITE_URL ?>" class="nav-logo">
            <div class="nav-logo-icon">🌊</div>
            <div class="nav-logo-text">Ocean<span>Travel</span></div>
        </a>
        <div class="nav-links">
            <a href="<?= SITE_URL ?>/#hoteles">Hoteles</a>
            <a href="<?= SITE_URL ?>/#promociones">Promociones</a>
            <a href="<?= SITE_URL ?>/#contacto">Contacto</a>
        </div>
        <div class="nav-actions">
            <?php if (isset($_SESSION['id_usuario'])): ?>
                <a href="<?= SITE_URL ?>/pages/cliente/mis-reservas.php" class="btn-nav-outline">Mis reservas</a>
                <a href="<?= SITE_URL ?>/pages/logout.php" class="btn-nav-outline">Salir</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/pages/login.php" class="btn-nav-outline">Iniciar sesión</a>
                <a href="<?= SITE_URL ?>/pages/registro.php" class="btn-nav-gold">Registrarse</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Hero con galería -->
<div style="padding-top:var(--navbar-h);">
    <?php $fotos = $hotel['fotos']; ?>
    <?php if (!empty($fotos)): ?>
    <div class="container" style="padding-top:1.5rem;">
        <div class="gallery-grid">
            <?php foreach (array_slice($fotos, 0, 5) as $i => $foto): ?>
            <img src="<?= UPLOAD_URL ?>hoteles/<?= $id ?>/<?= htmlspecialchars($foto['url_foto']) ?>"
                 alt="<?= htmlspecialchars($hotel['nombre']) ?>"
                 class="<?= $i === 0 ? 'main-photo' : '' ?>"
                 onclick="abrirFoto('<?= UPLOAD_URL ?>hoteles/<?= $id ?>/<?= htmlspecialchars($foto['url_foto']) ?>')">
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="hotel-hero">
        <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--ocean-deep),var(--ocean-bright));display:flex;align-items:center;justify-content:center;">
            <span style="font-size:5rem;">🏨</span>
        </div>
        <div class="hotel-hero-overlay"></div>
    </div>
    <?php endif; ?>
</div>

<!-- Contenido principal -->
<div class="container" style="padding-bottom:4rem;">
    <div class="detail-layout">

        <!-- Columna izquierda -->
        <div>
            <!-- Cabecera -->
            <div style="margin:1.5rem 0;">
                <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-bottom:0.5rem;">
                    <span style="color:var(--gold);font-size:1.1rem;"><?= str_repeat('★', $hotel['estrellas']) ?><?= str_repeat('☆', 5 - $hotel['estrellas']) ?></span>
                    <span class="badge badge-info"><?= htmlspecialchars($hotel['sector'] ?? $hotel['ciudad']) ?></span>
                    <span class="badge badge-success">Disponible</span>
                </div>
                <h1 style="font-size:2rem;margin-bottom:0.5rem;"><?= htmlspecialchars($hotel['nombre']) ?></h1>
                <div style="display:flex;align-items:center;gap:1rem;color:var(--text-secondary);font-size:0.88rem;">
                    <span>📍 <?= htmlspecialchars($hotel['direccion']) ?></span>
                    <?php if ($hotel['telefono']): ?>
                    <span>📞 <?= htmlspecialchars($hotel['telefono']) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($hotel['total_resenas'] > 0): ?>
                <div style="display:flex;align-items:center;gap:8px;margin-top:0.75rem;">
                    <span style="color:var(--gold);font-size:1.1rem;">★</span>
                    <strong><?= number_format($hotel['puntuacion_promedio'], 1) ?></strong>
                    <span style="color:var(--text-secondary);font-size:0.85rem;">(<?= $hotel['total_resenas'] ?> reseñas)</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Descripción -->
            <div class="card" style="margin-bottom:1.5rem;">
                <div class="card-header"><h3>Sobre el hotel</h3></div>
                <div class="card-body">
                    <p style="color:var(--text-secondary);line-height:1.8;"><?= nl2br(htmlspecialchars($hotel['descripcion'])) ?></p>
                </div>
            </div>

            <!-- Servicios -->
            <?php if (!empty($hotel['servicios'])): ?>
            <div class="card" style="margin-bottom:1.5rem;">
                <div class="card-header"><h3>Servicios y amenidades</h3></div>
                <div class="card-body">
                    <?php foreach ($hotel['servicios'] as $s): ?>
                        <span class="servicio-chip">✓ <?= htmlspecialchars($s['nombre']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Habitaciones disponibles -->
            <?php if (!empty($habitacionesDisp)): ?>
            <div class="card" style="margin-bottom:1.5rem;">
                <div class="card-header">
                    <h3>Habitaciones disponibles</h3>
                    <small style="color:var(--text-secondary);">
                        <?= date('d/m/Y', strtotime($entrada)) ?> → <?= date('d/m/Y', strtotime($salida)) ?>
                    </small>
                </div>
                <div class="card-body">
                    <?php foreach ($habitacionesDisp as $hab): ?>
                    <div class="hab-card" id="hab-<?= $hab['id_habitacion'] ?>">
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
                            <div>
                                <h4><?= htmlspecialchars($hab['tipo_nombre']) ?></h4>
                                <p style="font-size:0.85rem;color:var(--text-secondary);margin:0.3rem 0;">
                                    👥 Hasta <?= $hab['capacidad'] ?> personas &nbsp;·&nbsp;
                                    🛏️ Hab. <?= htmlspecialchars($hab['numero']) ?> &nbsp;·&nbsp;
                                    🏢 Piso <?= $hab['piso'] ?>
                                </p>
                                <?php if ($hab['tipo_descripcion']): ?>
                                <p style="font-size:0.83rem;color:var(--text-secondary);"><?= htmlspecialchars($hab['tipo_descripcion']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div style="text-align:right;">
                                <div style="font-size:1.4rem;font-weight:600;color:var(--ocean-deep);">
                                    $<?= number_format($hab['precio_base'], 2) ?>
                                </div>
                                <div style="font-size:0.78rem;color:var(--text-secondary);">por noche</div>
                                <button class="btn btn-primary btn-sm" style="margin-top:0.5rem;"
                                        onclick="seleccionarHabitacion(<?= $hab['id_habitacion'] ?>, '<?= htmlspecialchars($hab['tipo_nombre']) ?>', <?= $hab['precio_base'] ?>)">
                                    Reservar
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reseñas -->
            <?php if (!empty($resenas)): ?>
            <div class="card">
                <div class="card-header"><h3>Reseñas de huéspedes</h3></div>
                <div class="card-body">
                    <div class="reviews-grid">
                        <?php foreach ($resenas as $r): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="review-avatar"><?= strtoupper(substr($r['nombre'], 0, 1)) ?></div>
                                <div class="review-meta">
                                    <strong><?= htmlspecialchars($r['nombre'] . ' ' . $r['apellido']) ?></strong>
                                    <span><?= date('d/m/Y', strtotime($r['fecha_resena'])) ?></span>
                                </div>
                            </div>
                            <div class="review-stars"><?= str_repeat('★', $r['puntuacion']) ?><?= str_repeat('☆', 5 - $r['puntuacion']) ?></div>
                            <?php if ($r['titulo']): ?><strong style="font-size:0.88rem;"><?= htmlspecialchars($r['titulo']) ?></strong><?php endif; ?>
                            <p class="review-text"><?= htmlspecialchars($r['comentario']) ?></p>
                            <?php if ($r['respuesta_hotel']): ?>
                            <div style="background:var(--ocean-pale);border-left:3px solid var(--ocean-bright);padding:0.6rem 0.75rem;border-radius:0 var(--radius-sm) var(--radius-sm) 0;margin-top:0.75rem;font-size:0.82rem;">
                                <strong style="color:var(--ocean-bright);">OceanTravel:</strong>
                                <span style="color:var(--text-secondary);"> <?= htmlspecialchars($r['respuesta_hotel']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Booking card (derecha) -->
        <div>
            <div class="booking-card">
                <div style="margin-bottom:1.25rem;">
                    <span style="font-size:1.6rem;font-weight:600;color:var(--ocean-deep);">
                        Desde $<?= number_format(min(array_column($hotel['habitaciones'] ?: [['precio_base'=>0]], 'precio_base')), 0) ?>
                    </span>
                    <span style="font-size:0.85rem;color:var(--text-secondary);"> / noche</span>
                </div>

                <form method="GET" action="">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="form-group">
                        <label>Fecha de entrada</label>
                        <input type="date" name="entrada" class="form-control"
                               value="<?= htmlspecialchars($entrada) ?>"
                               min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Fecha de salida</label>
                        <input type="date" name="salida" class="form-control"
                               value="<?= htmlspecialchars($salida) ?>"
                               min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Huéspedes</label>
                        <select name="huespedes" class="form-control">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?= $i ?>" <?= $huespedes == $i ? 'selected' : '' ?>>
                                    <?= $i ?> persona<?= $i > 1 ? 's' : '' ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        🔍 Ver habitaciones disponibles
                    </button>
                </form>

                <?php if ($hotel['telefono'] || $hotel['email_contacto']): ?>
                <div style="border-top:1px solid var(--gray-200);margin-top:1.25rem;padding-top:1.25rem;">
                    <p style="font-size:0.82rem;color:var(--text-secondary);margin-bottom:0.75rem;">¿Preguntas? Contáctanos directamente:</p>
                    <?php if ($hotel['telefono']): ?>
                    <a href="tel:<?= $hotel['telefono'] ?>" style="display:flex;align-items:center;gap:8px;font-size:0.85rem;color:var(--ocean-bright);margin-bottom:0.4rem;">
                        📞 <?= htmlspecialchars($hotel['telefono']) ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($hotel['email_contacto']): ?>
                    <a href="mailto:<?= $hotel['email_contacto'] ?>" style="display:flex;align-items:center;gap:8px;font-size:0.85rem;color:var(--ocean-bright);">
                        ✉️ <?= htmlspecialchars($hotel['email_contacto']) ?>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Modal foto -->
<div id="modalFoto" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.9);z-index:9999;align-items:center;justify-content:center;" onclick="this.style.display='none'">
    <img id="modalFotoImg" src="" style="max-width:90%;max-height:90vh;border-radius:var(--radius-md);">
</div>

<script>
function abrirFoto(src) {
    document.getElementById('modalFotoImg').src = src;
    document.getElementById('modalFoto').style.display = 'flex';
}

function seleccionarHabitacion(id, nombre, precio) {
    <?php if (!isset($_SESSION['id_usuario'])): ?>
        if (confirm('Necesitas iniciar sesión para hacer una reserva. ¿Ir al login?')) {
            window.location.href = '<?= SITE_URL ?>/pages/login.php';
        }
        return;
    <?php endif; ?>
    const entrada = document.querySelector('input[name="entrada"]').value;
    const salida  = document.querySelector('input[name="salida"]').value;
    if (!entrada || !salida) { alert('Por favor selecciona las fechas primero.'); return; }
    window.location.href = `<?= SITE_URL ?>/pages/cliente/reservar.php?id_habitacion=${id}&entrada=${entrada}&salida=${salida}`;
}
</script>
</body>
</html>
