<aside class="admin-sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="nav-logo-icon">🌊</div>
        <div class="sidebar-logo-text">Ocean<span>Travel</span></div>
    </div>

    <nav class="sidebar-nav">

        <div class="sidebar-section">
            <div class="sidebar-section-title">Principal</div>
            <a href="<?= SITE_URL ?>/pages/admin/dashboard.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="<?= SITE_URL ?>/pages/admin/reservas.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'reservas.php' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Reservas
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Hoteles</div>
            <a href="<?= SITE_URL ?>/pages/admin/hoteles/index.php" class="sidebar-link">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                Ver hoteles
            </a>
            <a href="<?= SITE_URL ?>/pages/admin/hoteles/crear.php" class="sidebar-link">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                Agregar hotel
            </a>
            <a href="<?= SITE_URL ?>/pages/admin/habitaciones.php" class="sidebar-link">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M2 4v16M22 4v16M2 12h20M6 4v4M18 4v4"/></svg>
                Habitaciones
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Comercial</div>
            <a href="<?= SITE_URL ?>/pages/admin/promociones/index.php" class="sidebar-link">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                Promociones
            </a>
            <a href="<?= SITE_URL ?>/pages/admin/resenas.php" class="sidebar-link">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                Reseñas
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Sistema</div>
            <a href="<?= SITE_URL ?>/pages/admin/usuarios.php" class="sidebar-link">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Usuarios
            </a>
            <a href="<?= SITE_URL ?>" class="sidebar-link">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                Ver sitio web
            </a>
        </div>

    </nav>

    <div class="sidebar-footer">
        <a href="<?= SITE_URL ?>/pages/logout.php">
            <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            Cerrar sesión
        </a>
    </div>
</aside>
