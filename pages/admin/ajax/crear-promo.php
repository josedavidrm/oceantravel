<?php
// crear-promo.php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/autoloader.php';
session_start();
Usuario::verificarSesion('admin');

$promoObj = new Promocion();
$result = $promoObj->crear([
    'id_hotel'        => !empty($_POST['id_hotel']) ? $_POST['id_hotel'] : null,
    'nombre'          => $_POST['nombre']          ?? '',
    'descripcion'     => $_POST['descripcion']     ?? '',
    'tipo_descuento'  => $_POST['tipo_descuento']  ?? 'porcentaje',
    'valor_descuento' => $_POST['valor_descuento'] ?? 0,
    'codigo_promo'    => strtoupper($_POST['codigo_promo'] ?? ''),
    'usos_maximos'    => !empty($_POST['usos_maximos']) ? $_POST['usos_maximos'] : null,
    'fecha_inicio'    => $_POST['fecha_inicio']    ?? '',
    'fecha_fin'       => $_POST['fecha_fin']       ?? '',
]);

header('Location: ' . SITE_URL . '/pages/admin/promociones/index.php?' . ($result['success'] ? 'created=1' : 'error=1'));
exit;
