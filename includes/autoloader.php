<?php
// ============================================================
//  OceanTravel — Autoloader de clases
//  Carga automáticamente cualquier clase desde /classes/
// ============================================================

spl_autoload_register(function (string $clase) {
    $archivo = __DIR__ . '/../classes/' . $clase . '.php';
    if (file_exists($archivo)) {
        require_once $archivo;
    }
});

// Funciones helper globales
function redirigir(string $url): void {
    header('Location: ' . SITE_URL . '/' . ltrim($url, '/'));
    exit;
}

function sanitizar(string $valor): string {
    return htmlspecialchars(strip_tags(trim($valor)));
}

function formatearPrecio(float $precio): string {
    return '$' . number_format($precio, 2);
}

function formatearFecha(string $fecha, string $formato = 'd/m/Y'): string {
    return date($formato, strtotime($fecha));
}

function estrellas(int $n): string {
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}

function esAdmin(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin';
}

function esEmpleado(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'empleado';
}

function esCliente(): bool {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'cliente';
}

function usuarioLogueado(): bool {
    return isset($_SESSION['id_usuario']);
}
