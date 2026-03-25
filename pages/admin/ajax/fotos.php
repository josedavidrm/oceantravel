<?php
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('admin');

header('Content-Type: application/json');

$data     = json_decode(file_get_contents('php://input'), true);
$accion   = $data['accion'] ?? '';
$hotelObj = new Hotel();

switch ($accion) {
    case 'eliminar':
        $result = $hotelObj->eliminarFoto((int)$data['id_foto']);
        echo json_encode($result);
        break;

    case 'portada':
        $ok = $hotelObj->marcarComoPortada((int)$data['id_foto'], (int)$data['id_hotel']);
        echo json_encode(['success' => $ok]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
}
