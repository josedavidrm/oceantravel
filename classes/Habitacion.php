<?php
// ============================================================
//  Clase Habitacion — Gestión de habitaciones y tipos
// ============================================================

require_once __DIR__ . '/Model.php';

class Habitacion extends Model {
    protected string $table = 'habitaciones';

    // -------------------------------------------------------
    //  TIPO DE HABITACIÓN
    // -------------------------------------------------------
    public function crearTipo(array $datos): array {
        $stmt = $this->db->prepare("
            INSERT INTO tipo_habitacion (id_hotel, nombre, descripcion, capacidad, precio_base, amenidades)
            VALUES (:id_hotel, :nombre, :descripcion, :capacidad, :precio_base, :amenidades)
        ");
        $stmt->execute([
            ':id_hotel'    => $datos['id_hotel'],
            ':nombre'      => htmlspecialchars($datos['nombre']),
            ':descripcion' => $datos['descripcion'] ?? null,
            ':capacidad'   => (int)($datos['capacidad'] ?? 2),
            ':precio_base' => (float)$datos['precio_base'],
            ':amenidades'  => isset($datos['amenidades']) ? json_encode($datos['amenidades']) : null,
        ]);
        return ['success' => true, 'id' => $this->db->lastInsertId(), 'message' => 'Tipo de habitación creado.'];
    }

    public function actualizarTipo(int $id, array $datos): array {
        $stmt = $this->db->prepare("
            UPDATE tipo_habitacion SET nombre = :nombre, descripcion = :descripcion,
                capacidad = :capacidad, precio_base = :precio_base, amenidades = :amenidades
            WHERE id_tipo = :id
        ");
        $stmt->execute([
            ':nombre'      => htmlspecialchars($datos['nombre']),
            ':descripcion' => $datos['descripcion'] ?? null,
            ':capacidad'   => (int)($datos['capacidad'] ?? 2),
            ':precio_base' => (float)$datos['precio_base'],
            ':amenidades'  => isset($datos['amenidades']) ? json_encode($datos['amenidades']) : null,
            ':id'          => $id,
        ]);
        return ['success' => true, 'message' => 'Tipo actualizado.'];
    }

    // -------------------------------------------------------
    //  HABITACIONES FÍSICAS
    // -------------------------------------------------------
    public function crear(array $datos): array {
        $stmt = $this->db->prepare("
            INSERT INTO habitaciones (id_hotel, id_tipo, numero, piso, estado)
            VALUES (:id_hotel, :id_tipo, :numero, :piso, 'libre')
        ");
        $stmt->execute([
            ':id_hotel' => $datos['id_hotel'],
            ':id_tipo'  => $datos['id_tipo'],
            ':numero'   => htmlspecialchars($datos['numero']),
            ':piso'     => (int)($datos['piso'] ?? 1),
        ]);
        return ['success' => true, 'id' => $this->db->lastInsertId(), 'message' => 'Habitación creada.'];
    }

    public function cambiarEstado(int $id, string $estado): bool {
        return $this->db->prepare("UPDATE habitaciones SET estado = ? WHERE id_habitacion = ?")
                        ->execute([$estado, $id]);
    }

    // -------------------------------------------------------
    //  BUSCAR habitaciones disponibles
    // -------------------------------------------------------
    public function buscarDisponibles(int $idHotel, string $entrada, string $salida, int $huespedes = 1): array {
        $stmt = $this->db->prepare("
            SELECT h.*, th.nombre AS tipo_nombre, th.descripcion AS tipo_descripcion,
                   th.precio_base, th.capacidad, th.amenidades,
                   f.url_foto AS foto_tipo
            FROM habitaciones h
            INNER JOIN tipo_habitacion th ON th.id_tipo = h.id_tipo
            LEFT JOIN fotos_hotel f ON f.id_hotel = h.id_hotel AND f.es_portada = 1
            WHERE h.id_hotel = ?
              AND h.estado = 'libre'
              AND th.capacidad >= ?
              AND th.activo = 1
              AND h.id_habitacion NOT IN (
                  SELECT id_habitacion FROM reservas
                  WHERE estado NOT IN ('cancelada','no_show')
                    AND fecha_entrada < ? AND fecha_salida > ?
              )
            ORDER BY th.precio_base ASC
        ");
        $stmt->execute([$idHotel, $huespedes, $salida, $entrada]);
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------
    //  LISTAR habitaciones de un hotel (panel empleado/admin)
    // -------------------------------------------------------
    public function listarPorHotel(int $idHotel): array {
        $stmt = $this->db->prepare("
            SELECT h.*, th.nombre AS tipo_nombre, th.precio_base
            FROM habitaciones h
            INNER JOIN tipo_habitacion th ON th.id_tipo = h.id_tipo
            WHERE h.id_hotel = ?
            ORDER BY h.piso, h.numero
        ");
        $stmt->execute([$idHotel]);
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------
    //  RESUMEN de estados (dashboard empleado)
    // -------------------------------------------------------
    public function getResumenEstados(int $idHotel): array {
        $stmt = $this->db->prepare("
            SELECT estado, COUNT(*) AS total
            FROM habitaciones WHERE id_hotel = ?
            GROUP BY estado
        ");
        $stmt->execute([$idHotel]);
        $rows = $stmt->fetchAll();
        $resumen = ['libre' => 0, 'ocupada' => 0, 'limpieza' => 0, 'mantenimiento' => 0];
        foreach ($rows as $r) $resumen[$r['estado']] = (int)$r['total'];
        return $resumen;
    }
}
