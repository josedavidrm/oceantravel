<?php
// ============================================================
//  Clase Reserva — Gestión de reservas hoteleras
// ============================================================

require_once __DIR__ . '/Model.php';

class Reserva extends Model {
    protected string $table = 'reservas';

    // -------------------------------------------------------
    //  CREAR reserva
    // -------------------------------------------------------
    public function crear(array $datos): array {
        // Validar disponibilidad
        if (!$this->verificarDisponibilidad($datos['id_habitacion'], $datos['fecha_entrada'], $datos['fecha_salida'])) {
            return ['success' => false, 'message' => 'La habitación no está disponible en esas fechas.'];
        }

        // Calcular precio
        $calculo = $this->calcularPrecio(
            $datos['id_habitacion'],
            $datos['fecha_entrada'],
            $datos['fecha_salida'],
            $datos['id_promocion'] ?? null
        );

        $codigo = $this->generarCodigo();

        $stmt = $this->db->prepare("
            INSERT INTO reservas (codigo_reserva, id_usuario, id_habitacion, id_promocion,
                fecha_entrada, fecha_salida, num_huespedes, precio_por_noche,
                descuento_aplicado, precio_total, notas_cliente)
            VALUES (:codigo, :id_usuario, :id_habitacion, :id_promocion,
                    :fecha_entrada, :fecha_salida, :num_huespedes, :precio_por_noche,
                    :descuento_aplicado, :precio_total, :notas_cliente)
        ");
        $stmt->execute([
            ':codigo'             => $codigo,
            ':id_usuario'         => $datos['id_usuario'],
            ':id_habitacion'      => $datos['id_habitacion'],
            ':id_promocion'       => $datos['id_promocion'] ?? null,
            ':fecha_entrada'      => $datos['fecha_entrada'],
            ':fecha_salida'       => $datos['fecha_salida'],
            ':num_huespedes'      => $datos['num_huespedes'] ?? 1,
            ':precio_por_noche'   => $calculo['precio_noche'],
            ':descuento_aplicado' => $calculo['descuento'],
            ':precio_total'       => $calculo['total'],
            ':notas_cliente'      => $datos['notas_cliente'] ?? null,
        ]);

        $id = (int)$this->db->lastInsertId();

        // Actualizar usos de promoción
        if (!empty($datos['id_promocion'])) {
            $this->db->prepare("UPDATE promociones SET usos_actuales = usos_actuales + 1 WHERE id_promocion = ?")
                     ->execute([$datos['id_promocion']]);
        }

        return [
            'success'       => true,
            'id_reserva'    => $id,
            'codigo'        => $codigo,
            'precio_total'  => $calculo['total'],
            'message'       => 'Reserva creada exitosamente.',
        ];
    }

    // -------------------------------------------------------
    //  CALCULAR PRECIO (precio base + temporada - descuento)
    // -------------------------------------------------------
    public function calcularPrecio(int $idHabitacion, string $entrada, string $salida, ?int $idPromocion = null): array {
        // Precio base de la habitación
        $stmt = $this->db->prepare("
            SELECT th.precio_base FROM habitaciones h
            INNER JOIN tipo_habitacion th ON th.id_tipo = h.id_tipo
            WHERE h.id_habitacion = ?
        ");
        $stmt->execute([$idHabitacion]);
        $precioBase = (float)$stmt->fetchColumn();

        // Verificar si hay temporada activa
        $stmt = $this->db->prepare("
            SELECT multiplicador_precio FROM temporadas
            WHERE id_hotel = (SELECT id_hotel FROM habitaciones WHERE id_habitacion = ?)
              AND ? BETWEEN fecha_inicio AND fecha_fin
              AND activo = 1
            ORDER BY multiplicador_precio DESC LIMIT 1
        ");
        $stmt->execute([$idHabitacion, $entrada]);
        $multiplicador = (float)($stmt->fetchColumn() ?: 1.0);

        $precioNoche = round($precioBase * $multiplicador, 2);

        // Número de noches
        $noches = (int)((strtotime($salida) - strtotime($entrada)) / 86400);

        $subtotal   = $precioNoche * $noches;
        $descuento  = 0;

        // Aplicar promoción
        if ($idPromocion) {
            $stmt = $this->db->prepare("
                SELECT * FROM promociones
                WHERE id_promocion = ? AND activo = 1
                  AND CURDATE() BETWEEN fecha_inicio AND fecha_fin
                  AND (usos_maximos IS NULL OR usos_actuales < usos_maximos)
            ");
            $stmt->execute([$idPromocion]);
            $promo = $stmt->fetch();

            if ($promo) {
                if ($promo['tipo_descuento'] === 'porcentaje') {
                    $descuento = round($subtotal * ($promo['valor_descuento'] / 100), 2);
                } else {
                    $descuento = min($promo['valor_descuento'], $subtotal);
                }
            }
        }

        return [
            'precio_noche'  => $precioNoche,
            'noches'        => $noches,
            'subtotal'      => $subtotal,
            'descuento'     => $descuento,
            'total'         => max(0, $subtotal - $descuento),
            'multiplicador' => $multiplicador,
        ];
    }

    // -------------------------------------------------------
    //  VERIFICAR DISPONIBILIDAD
    // -------------------------------------------------------
    public function verificarDisponibilidad(int $idHabitacion, string $entrada, string $salida): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM reservas
            WHERE id_habitacion = ?
              AND estado NOT IN ('cancelada', 'no_show')
              AND fecha_entrada < ? AND fecha_salida > ?
        ");
        $stmt->execute([$idHabitacion, $salida, $entrada]);
        return (int)$stmt->fetchColumn() === 0;
    }

    // -------------------------------------------------------
    //  CAMBIAR estado de reserva
    // -------------------------------------------------------
    public function cambiarEstado(int $id, string $estado, string $notasInternas = ''): bool {
        $sql = "UPDATE reservas SET estado = ?";
        $params = [$estado];

        if ($notasInternas) {
            $sql .= ", notas_internas = ?";
            $params[] = $notasInternas;
        }
        $sql .= " WHERE id_reserva = ?";
        $params[] = $id;

        return $this->db->prepare($sql)->execute($params);
    }

    // -------------------------------------------------------
    //  RESERVAS de un usuario (cliente)
    // -------------------------------------------------------
    public function getReservasUsuario(int $idUsuario): array {
        $stmt = $this->db->prepare("
            SELECT r.*, h.nombre AS hotel_nombre, h.sector, h.foto_portada,
                   hab.numero AS num_habitacion, th.nombre AS tipo_habitacion,
                   p.estado_pago
            FROM reservas r
            INNER JOIN habitaciones hab ON hab.id_habitacion = r.id_habitacion
            INNER JOIN hoteles h ON h.id_hotel = hab.id_hotel
            INNER JOIN tipo_habitacion th ON th.id_tipo = hab.id_tipo
            LEFT JOIN pagos p ON p.id_reserva = r.id_reserva
            WHERE r.id_usuario = ?
            ORDER BY r.fecha_creacion DESC
        ");
        $stmt->execute([$idUsuario]);
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------
    //  RESERVAS de un hotel (empleado/admin)
    // -------------------------------------------------------
    public function getReservasHotel(int $idHotel, string $estado = '', string $fecha = ''): array {
        $sql = "
            SELECT r.*, u.nombre, u.apellido, u.email, u.telefono,
                   hab.numero AS num_habitacion, th.nombre AS tipo_habitacion,
                   p.estado_pago, p.metodo_pago
            FROM reservas r
            INNER JOIN habitaciones hab ON hab.id_habitacion = r.id_habitacion
            INNER JOIN tipo_habitacion th ON th.id_tipo = hab.id_tipo
            INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
            LEFT JOIN pagos p ON p.id_reserva = r.id_reserva
            WHERE hab.id_hotel = ?
        ";
        $params = [$idHotel];

        if ($estado) { $sql .= " AND r.estado = ?"; $params[] = $estado; }
        if ($fecha)  { $sql .= " AND r.fecha_entrada = ?"; $params[] = $fecha; }

        $sql .= " ORDER BY r.fecha_entrada ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------
    //  VALIDAR código de promoción
    // -------------------------------------------------------
    public function validarPromocion(string $codigo): array {
        $stmt = $this->db->prepare("
            SELECT * FROM promociones
            WHERE codigo_promo = ? AND activo = 1
              AND CURDATE() BETWEEN fecha_inicio AND fecha_fin
              AND (usos_maximos IS NULL OR usos_actuales < usos_maximos)
        ");
        $stmt->execute([strtoupper(trim($codigo))]);
        $promo = $stmt->fetch();

        if (!$promo) return ['success' => false, 'message' => 'Código de promoción inválido o expirado.'];

        return ['success' => true, 'promocion' => $promo, 'message' => '¡Promoción aplicada!'];
    }

    // -------------------------------------------------------
    //  ESTADÍSTICAS para el dashboard admin
    // -------------------------------------------------------
    public function getEstadisticas(int $idHotel = 0): array {
        $where = $idHotel ? "AND hab.id_hotel = $idHotel" : '';
        $stmt = $this->db->query("
            SELECT
                COUNT(*)                                        AS total_reservas,
                SUM(CASE WHEN r.estado = 'confirmada'  THEN 1 ELSE 0 END) AS confirmadas,
                SUM(CASE WHEN r.estado = 'pendiente'   THEN 1 ELSE 0 END) AS pendientes,
                SUM(CASE WHEN r.estado = 'cancelada'   THEN 1 ELSE 0 END) AS canceladas,
                SUM(CASE WHEN r.estado = 'completada'  THEN 1 ELSE 0 END) AS completadas,
                COALESCE(SUM(r.precio_total), 0)               AS ingresos_totales,
                COALESCE(SUM(CASE WHEN MONTH(r.fecha_creacion) = MONTH(NOW())
                              THEN r.precio_total END), 0)     AS ingresos_mes
            FROM reservas r
            INNER JOIN habitaciones hab ON hab.id_habitacion = r.id_habitacion
            WHERE 1=1 $where
        ");
        return $stmt->fetch();
    }

    // -------------------------------------------------------
    //  HELPERS
    // -------------------------------------------------------
    private function generarCodigo(): string {
        $anio = date('Y');
        $stmt = $this->db->query("SELECT COUNT(*) FROM reservas WHERE YEAR(fecha_creacion) = $anio");
        $num  = (int)$stmt->fetchColumn() + 1;
        return 'OT-' . $anio . '-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
