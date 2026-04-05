<aside class="admin-sidebar" id="sidebar">
    <div class="sidebar-logo">
        <div class="nav-logo-icon">🌊</div>
        <div class="sidebar-logo-text">Ocean<span>Travel</span></div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section">
            <div class="sidebar-section-title">Principal</div>
            <a href="<?= SITE_URL ?>/pages/empleado/dashboard.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Reservas</div>
            <a href="<?= SITE_URL ?>/pages/empleado/reservas.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'reservas.php' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                Ver reservas
            </a>
            <a href="<?= SITE_URL ?>/pages/empleado/checkin.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'checkin.php' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
                Check-in
            </a>
            <a href="<?= SITE_URL ?>/pages/empleado/checkout.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'checkout.php' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Check-out
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Hotel</div>
            <a href="<?= SITE_URL ?>/pages/empleado/habitaciones.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'habitaciones.php' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M2 4v16M22 4v16M2 12h20M6 4v4M18 4v4"/></svg>
                Habitaciones
            </a>
            <a href="<?= SITE_URL ?>/pages/empleado/incidencias.php" class="sidebar-link <?= basename($_SERVER['PHP_SELF']) === 'incidencias.php' ? 'active' : '' ?>">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><circle cx="12" cy="16" r="1" fill="currentColor"/></svg>
                Incidencias
            </a>
        </div>

        <div class="sidebar-section">
            <div class="sidebar-section-title">Sistema</div>
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
