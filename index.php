<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/autoloader.php';

session_start();

// Cargar datos desde la BD
$hotelObj  = new Hotel();
$promoObj  = new Promocion();
$resenaObj = new Resena();

$hoteles    = $hotelObj->listarActivos();
$promociones = $promoObj->listarActivas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OceanTravel — Reservas hoteleras en Isla de Margarita</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>

<!-- ============================================================
     NAVBAR
     ============================================================ -->
<nav class="navbar transparent" id="navbar">
    <div class="container navbar-inner">

        <!-- Logo -->
        <a href="<?= SITE_URL ?>" class="nav-logo">
            <div class="nav-logo-icon">🌊</div>
            <div class="nav-logo-text">Ocean<span>Travel</span></div>
        </a>

        <!-- Links -->
        <div class="nav-links">
            <a href="#hoteles" class="active">Hoteles</a>
            <a href="#promociones">Promociones</a>
            <a href="#resenas">Reseñas</a>
            <a href="#contacto">Contacto</a>
        </div>

        <!-- Acciones -->
        <div class="nav-actions">
            <?php if (isset($_SESSION['id_usuario'])): ?>
                <div class="user-menu">
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['nombre'], 0, 1) . substr($_SESSION['apellido'], 0, 1)) ?>
                    </div>
                    <div class="user-dropdown">
                        <div class="dropdown-header">
                            <strong><?= htmlspecialchars($_SESSION['nombre'] . ' ' . $_SESSION['apellido']) ?></strong>
                            <span><?= htmlspecialchars($_SESSION['email']) ?></span>
                        </div>
                        <a href="<?= SITE_URL ?>/pages/cliente/mis-reservas.php" class="dropdown-item">
                            📋 Mis reservas
                        </a>
                        <a href="<?= SITE_URL ?>/pages/cliente/perfil.php" class="dropdown-item">
                            👤 Mi perfil
                        </a>
                        <?php if ($_SESSION['rol'] === 'admin'): ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= SITE_URL ?>/pages/admin/dashboard.php" class="dropdown-item">
                            ⚙️ Panel admin
                        </a>
                        <?php elseif ($_SESSION['rol'] === 'empleado'): ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= SITE_URL ?>/pages/empleado/dashboard.php" class="dropdown-item">
                            🏨 Panel empleado
                        </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="<?= SITE_URL ?>/pages/logout.php" class="dropdown-item danger">
                            🚪 Cerrar sesión
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/pages/login.php" class="btn-nav-outline">Iniciar sesión</a>
                <a href="<?= SITE_URL ?>/pages/registro.php" class="btn-nav-gold">Registrarse</a>
            <?php endif; ?>
        </div>

        <button class="nav-hamburger" onclick="toggleMenu()">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- ============================================================
     HERO
     ============================================================ -->
<section class="hero">
    <div class="hero-bg"></div>

    <div class="container hero-content">
        <div class="hero-badge">
            🌴 Isla de Margarita, Venezuela
        </div>

        <h1>
            Tu paraíso caribeño<br>
            te espera con<br>
            <em>OceanTravel</em>
        </h1>

        <p>Encuentra y reserva los mejores hoteles en la Isla de Margarita. Precios exclusivos, promociones de temporada y atención personalizada.</p>

        <!-- Buscador -->
        <div class="hero-search">
            <div class="search-field">
                <label>Destino</label>
                <input type="text" placeholder="Isla de Margarita" value="Isla de Margarita" readonly>
            </div>
            <div class="search-field">
                <label>Fecha entrada</label>
                <input type="date" id="fecha-entrada" min="<?= date('Y-m-d') ?>">
            </div>
            <div class="search-field">
                <label>Fecha salida</label>
                <input type="date" id="fecha-salida" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            <button class="btn btn-primary btn-lg" onclick="buscarHoteles()">
                🔍 Buscar
            </button>
        </div>

        <!-- Estadísticas -->
        <div class="hero-stats">
            <div class="stat-item">
                <strong><?= count($hoteles) ?>+</strong>
                <span>Hoteles disponibles</span>
            </div>
            <div class="stat-item">
                <strong>100%</strong>
                <span>Reservas confirmadas</span>
            </div>
            <div class="stat-item">
                <strong>24/7</strong>
                <span>Atención al cliente</span>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     HOTELES DESTACADOS
     ============================================================ -->
<section class="section" id="hoteles">
    <div class="container">
        <div class="section-header">
            <span class="label">Nuestros hoteles</span>
            <h2>Alojamientos destacados</h2>
            <p>Encuentra el hotel perfecto para tu estadía en la Perla del Caribe</p>
        </div>

        <?php if (empty($hoteles)): ?>
            <div class="alert alert-info" style="text-align:center;">
                Próximamente agregaremos hoteles disponibles. ¡Vuelve pronto!
            </div>
        <?php else: ?>
        <div class="hotels-grid">
            <?php foreach ($hoteles as $hotel): ?>
            <div class="hotel-card">
                <div class="hotel-card-img">
                    <?php if ($hotel['foto_portada_url']): ?>
                        <img src="<?= UPLOAD_URL ?>hoteles/<?= $hotel['id_hotel'] ?>/<?= htmlspecialchars($hotel['foto_portada_url']) ?>"
                             alt="<?= htmlspecialchars($hotel['nombre']) ?>">
                    <?php else: ?>
                        <img src="<?= SITE_URL ?>/public/images/hotel-placeholder.jpg"
                             alt="<?= htmlspecialchars($hotel['nombre']) ?>"
                             onerror="this.style.display='none';this.parentElement.style.background='linear-gradient(135deg,#0d2244,#1a4a7a)'">
                    <?php endif; ?>
                    <div class="hotel-stars">
                        <?= str_repeat('★', $hotel['estrellas']) ?><?= str_repeat('☆', 5 - $hotel['estrellas']) ?>
                    </div>
                    <div class="hotel-price">
                        Desde <strong>$<?= number_format($hotel['precio_desde'] ?? 0, 0) ?></strong>/noche
                    </div>
                </div>
                <div class="hotel-card-body">
                    <h3><?= htmlspecialchars($hotel['nombre']) ?></h3>
                    <p class="hotel-location">
                        📍 <?= htmlspecialchars($hotel['sector'] ?? $hotel['ciudad']) ?>
                    </p>
                    <div class="hotel-rating">
                        <span class="stars">
                            <?php
                            $rating = round($hotel['puntuacion_promedio'] * 2) / 2;
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $rating ? '★' : '☆';
                            }
                            ?>
                        </span>
                        <span>
                            <?= number_format($hotel['puntuacion_promedio'], 1) ?>
                            (<?= $hotel['total_resenas'] ?> reseñas)
                        </span>
                    </div>
                    <?php
                    $servicios = $hotelObj->getServicios($hotel['id_hotel']);
                    $serviciosMostrar = array_slice($servicios, 0, 4);
                    ?>
                    <?php if ($serviciosMostrar): ?>
                    <div class="hotel-amenities">
                        <?php foreach ($serviciosMostrar as $s): ?>
                            <span class="amenity-tag"><?= htmlspecialchars($s['nombre']) ?></span>
                        <?php endforeach; ?>
                        <?php if (count($servicios) > 4): ?>
                            <span class="amenity-tag">+<?= count($servicios) - 4 ?> más</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/pages/hotel.php?id=<?= $hotel['id_hotel'] ?>"
                       class="btn btn-primary btn-block">
                        Ver hotel y reservar
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================================
     PROMOCIONES
     ============================================================ -->
<section class="section section-dark" id="promociones">
    <div class="container">
        <div class="section-header">
            <span class="label" style="color:var(--gold);">Ofertas especiales</span>
            <h2 style="color:var(--white);">Promociones de temporada</h2>
            <p style="color:rgba(255,255,255,0.55);">Aprovecha nuestros descuentos exclusivos para la Isla de Margarita</p>
        </div>

        <?php if (empty($promociones)): ?>
            <p style="text-align:center;color:rgba(255,255,255,0.4);">No hay promociones activas en este momento.</p>
        <?php else: ?>
        <div class="promos-grid">
            <?php foreach ($promociones as $promo): ?>
            <div class="promo-card">
                <div class="promo-badge">
                    <?php if ($promo['tipo_descuento'] === 'porcentaje'): ?>
                        <?= $promo['valor_descuento'] ?>% OFF
                    <?php else: ?>
                        $<?= number_format($promo['valor_descuento'], 0) ?> OFF
                    <?php endif; ?>
                </div>
                <h3><?= htmlspecialchars($promo['nombre']) ?></h3>
                <p><?= htmlspecialchars($promo['descripcion'] ?? '') ?></p>
                <?php if ($promo['codigo_promo']): ?>
                <div class="promo-code">
                    <span><?= htmlspecialchars($promo['codigo_promo']) ?></span>
                    <button class="btn btn-sm btn-gold" onclick="copiarCodigo('<?= $promo['codigo_promo'] ?>')">
                        Copiar
                    </button>
                </div>
                <?php endif; ?>
                <p class="promo-validity">
                    Válido hasta: <?= date('d/m/Y', strtotime($promo['fecha_fin'])) ?>
                    <?php if ($promo['hotel_nombre']): ?>
                        · <?= htmlspecialchars($promo['hotel_nombre']) ?>
                    <?php else: ?>
                        · Todos los hoteles
                    <?php endif; ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================================
     POR QUÉ ELEGIRNOS
     ============================================================ -->
<section class="section section-alt">
    <div class="container">
        <div class="section-header">
            <span class="label">¿Por qué OceanTravel?</span>
            <h2>Tu experiencia, nuestra prioridad</h2>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:2rem;">
            <?php
            $beneficios = [
                ['🏖️', 'Hoteles verificados',    'Todos nuestros hoteles son inspeccionados y verificados para garantizar la mejor calidad.'],
                ['💰', 'Mejores precios',          'Encontramos las mejores tarifas disponibles con descuentos exclusivos de temporada.'],
                ['⚡', 'Reserva en minutos',       'Proceso de reserva simple y rápido. Confirmación inmediata por correo electrónico.'],
                ['🛡️', 'Pago seguro',             'Todas las transacciones están protegidas con encriptación de máxima seguridad.'],
            ];
            foreach ($beneficios as $b): ?>
            <div class="card" style="text-align:center;padding:2rem;">
                <div style="font-size:2.5rem;margin-bottom:1rem;"><?= $b[0] ?></div>
                <h4><?= $b[1] ?></h4>
                <p style="font-size:0.88rem;color:var(--text-secondary);margin-top:0.5rem;"><?= $b[2] ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     RESEÑAS
     ============================================================ -->
<section class="section" id="resenas">
    <div class="container">
        <div class="section-header">
            <span class="label">Opiniones</span>
            <h2>Lo que dicen nuestros huéspedes</h2>
            <p>Experiencias reales de quienes ya disfrutaron la Isla de Margarita con OceanTravel</p>
        </div>
        <?php
        // Reseñas de todos los hoteles
        $todasResenas = [];
        foreach ($hoteles as $h) {
            $res = $resenaObj->getResenasHotel($h['id_hotel'], 2);
            foreach ($res as $r) {
                $r['hotel_nombre'] = $h['nombre'];
                $todasResenas[] = $r;
            }
        }
        ?>
        <?php if (empty($todasResenas)): ?>
            <p style="text-align:center;color:var(--text-secondary);">
                Sé el primero en dejar una reseña después de tu estadía. 🌟
            </p>
        <?php else: ?>
        <div class="reviews-grid">
            <?php foreach (array_slice($todasResenas, 0, 6) as $resena): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="review-avatar">
                        <?= strtoupper(substr($resena['nombre'], 0, 1)) ?>
                    </div>
                    <div class="review-meta">
                        <strong><?= htmlspecialchars($resena['nombre'] . ' ' . $resena['apellido']) ?></strong>
                        <span><?= date('d/m/Y', strtotime($resena['fecha_resena'])) ?></span>
                    </div>
                </div>
                <div class="review-stars">
                    <?= str_repeat('★', $resena['puntuacion']) ?><?= str_repeat('☆', 5 - $resena['puntuacion']) ?>
                </div>
                <?php if ($resena['titulo']): ?>
                    <strong style="font-size:0.9rem;"><?= htmlspecialchars($resena['titulo']) ?></strong>
                <?php endif; ?>
                <p class="review-text"><?= htmlspecialchars($resena['comentario']) ?></p>
                <p class="review-hotel">🏨 <?= htmlspecialchars($resena['hotel_nombre']) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============================================================
     CONTACTO
     ============================================================ -->
<section class="section section-dark" id="contacto">
    <div class="container" style="text-align:center;">
        <span class="label" style="color:var(--gold);">¿Necesitas ayuda?</span>
        <h2 style="color:var(--white);margin:0.5rem 0 1rem;">Estamos para servirte</h2>
        <p style="color:rgba(255,255,255,0.55);max-width:480px;margin:0 auto 2rem;">
            Nuestro equipo está disponible para ayudarte a planificar la estadía perfecta en Isla de Margarita.
        </p>
        <div style="display:flex;justify-content:center;gap:1.5rem;flex-wrap:wrap;">
            <a href="mailto:info@oceantravel.com" class="btn btn-gold btn-lg">
                ✉️ info@oceantravel.com
            </a>
            <a href="tel:+582950000000" class="btn btn-outline btn-lg" style="color:var(--white);border-color:rgba(255,255,255,0.3);">
                📞 +58 295-000-0000
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     FOOTER
     ============================================================ -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="nav-logo">
                    <div class="nav-logo-icon">🌊</div>
                    <div class="nav-logo-text">Ocean<span>Travel</span></div>
                </div>
                <p>Tu agencia de confianza para reservas hoteleras en la hermosa Isla de Margarita, Venezuela. Calidad, comodidad y los mejores precios del Caribe.</p>
            </div>
            <div>
                <h4>Navegación</h4>
                <div class="footer-links">
                    <a href="#hoteles">Hoteles</a>
                    <a href="#promociones">Promociones</a>
                    <a href="#resenas">Reseñas</a>
                    <a href="#contacto">Contacto</a>
                </div>
            </div>
            <div>
                <h4>Mi cuenta</h4>
                <div class="footer-links">
                    <a href="<?= SITE_URL ?>/pages/login.php">Iniciar sesión</a>
                    <a href="<?= SITE_URL ?>/pages/registro.php">Crear cuenta</a>
                    <a href="<?= SITE_URL ?>/pages/cliente/mis-reservas.php">Mis reservas</a>
                </div>
            </div>
            <div>
                <h4>Contacto</h4>
                <div class="footer-contact">
                    <p>📍 Isla de Margarita, Venezuela</p>
                    <p>📞 +58 295-000-0000</p>
                    <p>✉️ info@oceantravel.com</p>
                    <p>🕐 Lun–Sab: 8am – 6pm</p>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?= date('Y') ?> OceanTravel. Todos los derechos reservados.</p>
            <p>Hecho con ❤️ en Venezuela</p>
        </div>
    </div>
</footer>

<!-- ============================================================
     JAVASCRIPT
     ============================================================ -->
<script>
// Navbar scroll
const navbar = document.getElementById('navbar');
window.addEventListener('scroll', () => {
    navbar.classList.toggle('scrolled', window.scrollY > 60);
    navbar.classList.toggle('transparent', window.scrollY <= 60);
});

// Validar fechas del buscador
const fechaEntrada = document.getElementById('fecha-entrada');
const fechaSalida  = document.getElementById('fecha-salida');

fechaEntrada.addEventListener('change', () => {
    const min = new Date(fechaEntrada.value);
    min.setDate(min.getDate() + 1);
    fechaSalida.min = min.toISOString().split('T')[0];
    if (fechaSalida.value && fechaSalida.value <= fechaEntrada.value) {
        fechaSalida.value = min.toISOString().split('T')[0];
    }
});

// Buscar hoteles
function buscarHoteles() {
    const entrada = fechaEntrada.value;
    const salida  = fechaSalida.value;
    if (!entrada || !salida) {
        alert('Por favor selecciona las fechas de entrada y salida.');
        return;
    }
    window.location.href = `<?= SITE_URL ?>/pages/hoteles.php?entrada=${entrada}&salida=${salida}`;
}

// Copiar código de promo
function copiarCodigo(codigo) {
    navigator.clipboard.writeText(codigo).then(() => {
        alert('¡Código "' + codigo + '" copiado al portapapeles!');
    });
}

// Menú hamburguesa
function toggleMenu() {
    document.querySelector('.nav-links').classList.toggle('open');
}

// Smooth scroll para los links del navbar
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        const target = document.querySelector(a.getAttribute('href'));
        if (target) target.scrollIntoView({ behavior: 'smooth' });
    });
});
</script>

</body>
</html>