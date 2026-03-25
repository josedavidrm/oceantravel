<?php
// ============================================================
//  Clase Resena — Valoraciones de clientes
// ============================================================

require_once __DIR__ . '/Model.php';

class Resena extends Model {
    protected string $table = 'resenas';

    public function crear(array $datos): array {
        // Solo puede reseñar si tiene reserva completada
        $stmt = $this->db->prepare("
            SELECT id_reserva FROM reservas
            WHERE id_reserva = ? AND id_usuario = ? AND estado = 'completada'
        ");
        $stmt->execute([$datos['id_reserva'], $datos['id_usuario']]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'message' => 'Solo puedes reseñar reservas completadas.'];
        }

        // Verificar que no haya reseñado ya
        $stmt = $this->db->prepare("SELECT id_resena FROM resenas WHERE id_reserva = ?");
        $stmt->execute([$datos['id_reserva']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Ya dejaste una reseña para esta reserva.'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO resenas (id_reserva, id_usuario, id_hotel, puntuacion, titulo, comentario)
            VALUES (:id_reserva, :id_usuario, :id_hotel, :puntuacion, :titulo, :comentario)
        ");
        $stmt->execute([
            ':id_reserva'  => $datos['id_reserva'],
            ':id_usuario'  => $datos['id_usuario'],
            ':id_hotel'    => $datos['id_hotel'],
            ':puntuacion'  => (int)$datos['puntuacion'],
            ':titulo'      => htmlspecialchars($datos['titulo'] ?? ''),
            ':comentario'  => htmlspecialchars($datos['comentario']),
        ]);

        return ['success' => true, 'message' => '¡Gracias por tu reseña!'];
    }

    public function getResenasHotel(int $idHotel, int $limite = 10): array {
        $stmt = $this->db->prepare("
            SELECT r.*, u.nombre, u.apellido, u.foto_perfil
            FROM resenas r
            INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
            WHERE r.id_hotel = ? AND r.visible = 1
            ORDER BY r.fecha_resena DESC
            LIMIT ?
        ");
        $stmt->execute([$idHotel, $limite]);
        return $stmt->fetchAll();
    }

    public function responderResena(int $id, string $respuesta): bool {
        return $this->db->prepare("UPDATE resenas SET respuesta_hotel = ? WHERE id_resena = ?")
                        ->execute([$respuesta, $id]);
    }

    public function toggleVisibilidad(int $id): bool {
        return $this->db->prepare("UPDATE resenas SET visible = NOT visible WHERE id_resena = ?")
                        ->execute([$id]);
    }
}
