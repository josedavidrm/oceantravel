<?php
// ============================================================
//  Clase Promocion — Gestión de descuentos y temporadas
// ============================================================

require_once __DIR__ . '/Model.php';

class Promocion extends Model {
    protected string $table = 'promociones';

    public function crear(array $datos): array {
        if (empty($datos['nombre']) || empty($datos['valor_descuento'])) {
            return ['success' => false, 'message' => 'Nombre y valor son obligatorios.'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO promociones (id_hotel, nombre, descripcion, tipo_descuento,
                valor_descuento, codigo_promo, usos_maximos, fecha_inicio, fecha_fin, activo)
            VALUES (:id_hotel, :nombre, :descripcion, :tipo_descuento,
                    :valor_descuento, :codigo_promo, :usos_maximos, :fecha_inicio, :fecha_fin, 1)
        ");
        $stmt->execute([
            ':id_hotel'       => $datos['id_hotel'] ?? null,
            ':nombre'         => htmlspecialchars($datos['nombre']),
            ':descripcion'    => $datos['descripcion'] ?? null,
            ':tipo_descuento' => $datos['tipo_descuento'] ?? 'porcentaje',
            ':valor_descuento'=> (float)$datos['valor_descuento'],
            ':codigo_promo'   => strtoupper($datos['codigo_promo'] ?? ''),
            ':usos_maximos'   => $datos['usos_maximos'] ?? null,
            ':fecha_inicio'   => $datos['fecha_inicio'],
            ':fecha_fin'      => $datos['fecha_fin'],
        ]);

        return ['success' => true, 'id' => $this->db->lastInsertId(), 'message' => 'Promoción creada.'];
    }

    public function actualizar(int $id, array $datos): array {
        $stmt = $this->db->prepare("
            UPDATE promociones SET nombre = :nombre, descripcion = :descripcion,
                tipo_descuento = :tipo_descuento, valor_descuento = :valor_descuento,
                fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin,
                activo = :activo WHERE id_promocion = :id
        ");
        $stmt->execute([
            ':nombre'         => htmlspecialchars($datos['nombre']),
            ':descripcion'    => $datos['descripcion'] ?? null,
            ':tipo_descuento' => $datos['tipo_descuento'],
            ':valor_descuento'=> (float)$datos['valor_descuento'],
            ':fecha_inicio'   => $datos['fecha_inicio'],
            ':fecha_fin'      => $datos['fecha_fin'],
            ':activo'         => $datos['activo'] ?? 1,
            ':id'             => $id,
        ]);
        return ['success' => true, 'message' => 'Promoción actualizada.'];
    }

    public function listarActivas(): array {
        $stmt = $this->db->query("
            SELECT p.*, h.nombre AS hotel_nombre FROM promociones p
            LEFT JOIN hoteles h ON h.id_hotel = p.id_hotel
            WHERE p.activo = 1 AND CURDATE() BETWEEN p.fecha_inicio AND p.fecha_fin
            ORDER BY p.fecha_fin ASC
        ");
        return $stmt->fetchAll();
    }

    public function listarTodas(): array {
        $stmt = $this->db->query("
            SELECT p.*, h.nombre AS hotel_nombre FROM promociones p
            LEFT JOIN hoteles h ON h.id_hotel = p.id_hotel
            ORDER BY p.fecha_inicio DESC
        ");
        return $stmt->fetchAll();
    }

    public function toggleEstado(int $id): bool {
        return $this->db->prepare("UPDATE promociones SET activo = NOT activo WHERE id_promocion = ?")
                        ->execute([$id]);
    }
}
