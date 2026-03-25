<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/autoloader.php';

session_start();
Usuario::verificarSesion('cliente');

$idHabitacion = (int)($_GET['id_habitacion'] ?? 0);
$entrada      = $_GET['entrada'] ?? '';
$salida       = $_GET['salida']  ?? '';

if (!$idHabitacion || !$entrada || !$salida) {
    header('Location: ' . SITE_URL);
    exit;
}

$reservaObj    = new Reserva();
$habitacionObj = new Habitacion();
$hotelObj      = new Hotel();
$db            = Database::getInstance()->getConnection();

// Obtener datos de la habitación
$stmt = $db->prepare("
    SELECT h.*, th.nombre AS tipo_nombre, th.descripcion AS tipo_desc,
           th.precio_base, th.capacidad, hot.nombre AS hotel_nombre,
           hot.id_hotel, hot.sector, hot.direccion
    FROM habitaciones h
    INNER JOIN tipo_habitacion th ON th.id_tipo = h.id_tipo
    INNER JOIN hoteles hot ON hot.id_hotel = h.id_hotel
    WHERE h.id_habitacion = ?
");
$stmt->execute([$idHabitacion]);
$habitacion = $stmt->fetch();

if (!$habitacion) { header('Location: ' . SITE_URL); exit; }

// Verificar disponibilidad
if (!$reservaObj->verificarDisponibilidad($idHabitacion, $entrada, $salida)) {
    header('Location: ' . SITE_URL . '/pages/hotel.php?id=' . $habitacion['id_hotel'] . '&error=no_disponible');
    exit;
}

// Calcular precio inicial
$calculo = $reservaObj->calcularPrecio($idHabitacion, $entrada, $salida);
$idPromo = null;
$promoData = null;

// Aplicar código de promoción vía AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aplicar_promo'])) {
    $promoResult = $reservaObj->validarPromocion($_POST['codigo_promo'] ?? '');
    if ($promoResult['success']) {
        $idPromo   = $promoResult['promocion']['id_promocion'];
        $promoData = $promoResult['promocion'];
        $calculo   = $reservaObj->calcularPrecio($idHabitacion, $entrada, $salida, $idPromo);
    }
    $promoError = $promoResult['success'] ? '' : $promoResult['message'];
}

// Confirmar reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_reserva'])) {
    $result = $reservaObj->crear([
        'id_usuario'    => $_SESSION['id_usuario'],
        'id_habitacion' => $idHabitacion,
        'id_promocion'  => !empty($_POST['id_promocion']) ? (int)$_POST['id_promocion'] : null,
        'fecha_entrada' => $entrada,
        'fecha_salida'  => $salida,
        'num_huespedes' => (int)($_POST['num_huespedes'] ?? 1),
        'notas_cliente' => $_POST['notas_cliente'] ?? '',
    ]);

    if ($result['success']) {
        // Registrar pago pendiente
        $db->prepare("
            INSERT INTO pagos (id_reserva, monto, metodo_pago, estado_pago)
            VALUES (?, ?, ?, 'pendiente')
        ")->execute([$result['id_reserva'], $result['precio_total'], $_POST['metodo_pago'] ?? 'transferencia']);

        header('Location: ' . SITE_URL . '/pages/cliente/confirmacion.php?id=' . $result['id_reserva']);
        exit;
    } else {
        $errorReserva = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar — <?= htmlspecialchars($habitacion['tipo_nombre']) ?> | OceanTravel</title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/public/css/styles.css">
</head>
<body>

<nav class="navbar solid">
    <div class="container navbar-inner">
        <a href="<?= SITE_URL ?>" class="nav-logo">
            <div class="nav-logo-icon">🌊</div>
            <div class="nav-logo-text">Ocean<span>Travel</span></div>
        </a>
        <div class="nav-actions">
            <a href="<?= SITE_URL ?>/pages/hotel.php?id=<?= $habitacion['id_hotel'] ?>" class="btn-nav-outline">← Volver al hotel</a>
        </div>
    </div>
</nav>

<div style="padding-top:calc(var(--navbar-h) + 2rem);padding-bottom:4rem;">
    <div class="container">

        <!-- Pasos -->
        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:2rem;font-size:0.85rem;">
            <span style="color:var(--text-secondary);">1. Seleccionar habitación</span>
            <span style="color:var(--gray-400);">→</span>
            <strong style="color:var(--ocean-bright);">2. Confirmar reserva</strong>
            <span style="color:var(--gray-400);">→</span>
            <span style="color:var(--text-secondary);">3. Confirmación</span>
        </div>

        <?php if (isset($errorReserva)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorReserva) ?></div>
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 380px;gap:2rem;">

            <!-- Formulario -->
            <div>
                <form method="POST" action="" id="formReserva">

                    <!-- Detalles de la reserva -->
                    <div class="card" style="margin-bottom:1.5rem;">
                        <div class="card-header"><h3>Detalles de tu estancia</h3></div>
                        <div class="card-body">
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Fecha de entrada</label>
                                    <input type="text" class="form-control" value="<?= date('d/m/Y', strtotime($entrada)) ?>" readonly style="background:var(--gray-100);">
                                </div>
                                <div class="form-group">
                                    <label>Fecha de salida</label>
                                    <input type="text" class="form-control" value="<?= date('d/m/Y', strtotime($salida)) ?>" readonly style="background:var(--gray-100);">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Número de huéspedes</label>
                                <select name="num_huespedes" class="form-control">
                                    <?php for ($i = 1; $i <= $habitacion['capacidad']; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?> persona<?= $i > 1 ? 's' : '' ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Notas o solicitudes especiales (opcional)</label>
                                <textarea name="notas_cliente" class="form-control" rows="3"
                                          placeholder="Ej: habitación en piso alto, cama extra, llegada tardía..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Código de promoción -->
                    <div class="card" style="margin-bottom:1.5rem;">
                        <div class="card-header"><h3>Código de promoción</h3></div>
                        <div class="card-body">
                            <?php if (isset($promoData)): ?>
                                <div class="alert alert-success">
                                    ✅ Promoción <strong><?= htmlspecialchars($promoData['nombre']) ?></strong> aplicada —
                                    <?= $promoData['tipo_descuento'] === 'porcentaje' ? $promoData['valor_descuento'] . '%' : '$' . $promoData['valor_descuento'] ?> de descuento
                                </div>
                                <input type="hidden" name="id_promocion" value="<?= $idPromo ?>">
                            <?php else: ?>
                                <?php if (isset($promoError) && $promoError): ?>
                                    <div class="alert alert-danger"><?= htmlspecialchars($promoError) ?></div>
                                <?php endif; ?>
                                <div style="display:flex;gap:0.75rem;">
                                    <input type="text" name="codigo_promo" class="form-control"
                                           placeholder="Ingresa tu código de descuento"
                                           style="text-transform:uppercase;">
                                    <button type="submit" name="aplicar_promo" value="1"
                                            class="btn btn-outline" style="white-space:nowrap;">
                                        Aplicar
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Método de pago -->
                    <div class="card" style="margin-bottom:1.5rem;">
                        <div class="card-header"><h3>Método de pago</h3></div>
                        <div class="card-body">
                            <?php
                            $metodos = [
                                'transferencia'     => '🏦 Transferencia bancaria',
                                'pago_movil'        => '📱 Pago móvil',
                                'tarjeta_credito'   => '💳 Tarjeta de crédito',
                                'tarjeta_debito'    => '💳 Tarjeta de débito',
                                'efectivo'          => '💵 Efectivo (en hotel)',
                            ];
                            foreach ($metodos as $val => $label): ?>
                            <label style="display:flex;align-items:center;gap:10px;padding:0.75rem;border:1.5px solid var(--gray-200);border-radius:var(--radius-sm);margin-bottom:0.5rem;cursor:pointer;transition:border-color 0.2s;">
                                <input type="radio" name="metodo_pago" value="<?= $val ?>" <?= $val === 'transferencia' ? 'checked' : '' ?> style="accent-color:var(--ocean-bright);">
                                <?= $label ?>
                            </label>
                            <?php endforeach; ?>
                            <p style="font-size:0.8rem;color:var(--text-secondary);margin-top:0.75rem;">
                                ℹ️ La reserva quedará en estado <strong>pendiente</strong> hasta que se confirme el pago.
                            </p>
                        </div>
                    </div>

                    <input type="hidden" name="confirmar_reserva" value="1">
                    <?php if ($idPromo): ?><input type="hidden" name="id_promocion" value="<?= $idPromo ?>"><?php endif; ?>
                    <button type="submit" class="btn btn-gold btn-block btn-lg">
                        ✅ Confirmar reserva
                    </button>

                </form>
            </div>

            <!-- Resumen -->
            <div>
                <div class="booking-card" style="position:sticky;top:calc(var(--navbar-h) + 1rem);">
                    <h3 style="font-family:'Poppins',sans-serif;font-size:1rem;margin-bottom:1.25rem;">Resumen de tu reserva</h3>

                    <div style="background:var(--ocean-pale);border-radius:var(--radius-md);padding:1rem;margin-bottom:1.25rem;">
                        <strong style="color:var(--ocean-deep);"><?= htmlspecialchars($habitacion['hotel_nombre']) ?></strong><br>
                        <span style="font-size:0.85rem;color:var(--text-secondary);">📍 <?= htmlspecialchars($habitacion['sector'] ?? '') ?></span><br>
                        <span style="font-size:0.85rem;color:var(--text-secondary);">🛏️ <?= htmlspecialchars($habitacion['tipo_nombre']) ?> — Hab. <?= htmlspecialchars($habitacion['numero']) ?></span>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:0.6rem;font-size:0.88rem;">
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-secondary);">Check-in</span>
                            <strong><?= date('d/m/Y', strtotime($entrada)) ?></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-secondary);">Check-out</span>
                            <strong><?= date('d/m/Y', strtotime($salida)) ?></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-secondary);">Noches</span>
                            <strong><?= $calculo['noches'] ?></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--text-secondary);">Precio/noche</span>
                            <strong>$<?= number_format($calculo['precio_noche'], 2) ?></strong>
                        </div>
                        <?php if ($calculo['multiplicador'] != 1): ?>
                        <div style="display:flex;justify-content:space-between;color:var(--warning);">
                            <span>Temporada especial</span>
                            <span>×<?= $calculo['multiplicador'] ?></span>
                        </div>
                        <?php endif; ?>

                        <div style="border-top:1px solid var(--gray-200);padding-top:0.6rem;display:flex;justify-content:space-between;">
                            <span style="color:var(--text-secondary);">Subtotal</span>
                            <span>$<?= number_format($calculo['subtotal'], 2) ?></span>
                        </div>
                        <?php if ($calculo['descuento'] > 0): ?>
                        <div style="display:flex;justify-content:space-between;color:var(--success);">
                            <span>Descuento</span>
                            <span>-$<?= number_format($calculo['descuento'], 2) ?></span>
                        </div>
                        <?php endif; ?>

                        <div style="border-top:2px solid var(--ocean-deep);padding-top:0.75rem;display:flex;justify-content:space-between;font-size:1.1rem;">
                            <strong>Total</strong>
                            <strong style="color:var(--ocean-bright);">$<?= number_format($calculo['total'], 2) ?></strong>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
