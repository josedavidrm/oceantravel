<?php
// ============================================================
//  Clase Hotel — Gestión de hoteles y fotos
// ============================================================

require_once __DIR__ . '/Model.php';

class Hotel extends Model {
    protected string $table = 'hoteles';

    // Clave primaria explícita para evitar errores de generación automática
    protected function getPrimaryKey(): string {
        return 'id_hotel';
    }

    // -------------------------------------------------------
    //  CREAR hotel
    // -------------------------------------------------------
    public function crear(array $datos): array {
        if (empty($datos['nombre']) || empty($datos['descripcion'])) {
            return ['success' => false, 'message' => 'Nombre y descripción son obligatorios.'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO hoteles (nombre, descripcion, ciudad, sector, direccion, telefono,
                                 email_contacto, sitio_web, estrellas, estado)
            VALUES (:nombre, :descripcion, :ciudad, :sector, :direccion, :telefono,
                    :email_contacto, :sitio_web, :estrellas, :estado)
        ");
        $stmt->execute([
            ':nombre'         => htmlspecialchars(trim($datos['nombre'])),
            ':descripcion'    => htmlspecialchars($datos['descripcion']),
            ':ciudad'         => $datos['ciudad'] ?? 'Isla de Margarita',
            ':sector'         => $datos['sector'] ?? null,
            ':direccion'      => htmlspecialchars($datos['direccion'] ?? ''),
            ':telefono'       => $datos['telefono'] ?? null,
            ':email_contacto' => $datos['email_contacto'] ?? null,
            ':sitio_web'      => $datos['sitio_web'] ?? null,
            ':estrellas'      => (int)($datos['estrellas'] ?? 3),
            ':estado'         => $datos['estado'] ?? 'activo',
        ]);

        $id = (int)$this->db->lastInsertId();
        return ['success' => true, 'id' => $id, 'message' => 'Hotel creado exitosamente.'];
    }

    // -------------------------------------------------------
    //  ACTUALIZAR hotel
    // -------------------------------------------------------
    public function actualizar(int $id, array $datos): array {
        $stmt = $this->db->prepare("
            UPDATE hoteles SET nombre = :nombre, descripcion = :descripcion,
                ciudad = :ciudad, sector = :sector, direccion = :direccion,
                telefono = :telefono, email_contacto = :email_contacto,
                sitio_web = :sitio_web, estrellas = :estrellas, estado = :estado
            WHERE id_hotel = :id
        ");
        $stmt->execute([
            ':nombre'         => htmlspecialchars(trim($datos['nombre'])),
            ':descripcion'    => htmlspecialchars($datos['descripcion']),
            ':ciudad'         => $datos['ciudad'] ?? 'Isla de Margarita',
            ':sector'         => $datos['sector'] ?? null,
            ':direccion'      => htmlspecialchars($datos['direccion'] ?? ''),
            ':telefono'       => $datos['telefono'] ?? null,
            ':email_contacto' => $datos['email_contacto'] ?? null,
            ':sitio_web'      => $datos['sitio_web'] ?? null,
            ':estrellas'      => (int)($datos['estrellas'] ?? 3),
            ':estado'         => $datos['estado'] ?? 'activo',
            ':id'             => $id,
        ]);
        return ['success' => true, 'message' => 'Hotel actualizado exitosamente.'];
    }

    // -------------------------------------------------------
    //  LISTAR hoteles activos (para el sitio público)
    // -------------------------------------------------------
    public function listarActivos(array $filtros = []): array {
        $sql = "
            SELECT h.*,
                   COALESCE(AVG(r.puntuacion), 0) AS puntuacion_promedio,
                   COUNT(DISTINCT r.id_resena) AS total_resenas,
                   MIN(th.precio_base) AS precio_desde,
                   f.url_foto AS foto_portada_url
            FROM hoteles h
            LEFT JOIN resenas r ON r.id_hotel = h.id_hotel AND r.visible = 1
            LEFT JOIN tipo_habitacion th ON th.id_hotel = h.id_hotel AND th.activo = 1
            LEFT JOIN fotos_hotel f ON f.id_hotel = h.id_hotel AND f.es_portada = 1
            WHERE h.estado = 'activo'
        ";
        $params = [];

        if (!empty($filtros['sector'])) {
            $sql .= " AND h.sector = ?";
            $params[] = $filtros['sector'];
        }
        if (!empty($filtros['estrellas'])) {
            $sql .= " AND h.estrellas = ?";
            $params[] = (int)$filtros['estrellas'];
        }
        if (!empty($filtros['precio_max'])) {
            $sql .= " HAVING precio_desde <= ?";
            $params[] = (float)$filtros['precio_max'];
        }

        $sql .= " GROUP BY h.id_hotel ORDER BY h.estrellas DESC, puntuacion_promedio DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------
    //  DETALLE completo de un hotel
    // -------------------------------------------------------
    public function getDetalle(int $id): array|false {
        $stmt = $this->db->prepare("
            SELECT h.*,
                   COALESCE(AVG(r.puntuacion), 0) AS puntuacion_promedio,
                   COUNT(DISTINCT r.id_resena) AS total_resenas
            FROM hoteles h
            LEFT JOIN resenas r ON r.id_hotel = h.id_hotel AND r.visible = 1
            WHERE h.id_hotel = ?
            GROUP BY h.id_hotel
        ");
        $stmt->execute([$id]);
        $hotel = $stmt->fetch();
        if (!$hotel) return false;

        $hotel['fotos']        = $this->getFotos($id);
        $hotel['servicios']    = $this->getServicios($id);
        $hotel['habitaciones'] = $this->getTiposHabitacion($id);

        return $hotel;
    }

    // -------------------------------------------------------
    //  SUBIR FOTO desde el panel de administración
    // -------------------------------------------------------
    public function subirFoto(int $idHotel, array $archivo, string $descripcion = '', bool $esPortada = false): array {
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Error al subir la imagen.'];
        }
        if (!in_array($archivo['type'], ALLOWED_IMAGE_TYPES)) {
            return ['success' => false, 'message' => 'Solo se permiten imágenes JPG, PNG o WebP.'];
        }
        if ($archivo['size'] > MAX_UPLOAD_SIZE) {
            return ['success' => false, 'message' => 'La imagen no debe superar 5MB.'];
        }

        $carpeta = UPLOAD_PATH . 'hoteles/' . $idHotel . '/';
        if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);

        $ext     = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        $nombre  = 'hotel_' . $idHotel . '_' . uniqid() . '.' . $ext;
        $destino = $carpeta . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            return ['success' => false, 'message' => 'No se pudo guardar la imagen en el servidor.'];
        }

        if ($esPortada) {
            $this->db->prepare("UPDATE fotos_hotel SET es_portada = 0 WHERE id_hotel = ?")
                     ->execute([$idHotel]);
            $this->db->prepare("UPDATE hoteles SET foto_portada = ? WHERE id_hotel = ?")
                     ->execute([$nombre, $idHotel]);
        }

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM fotos_hotel WHERE id_hotel = ?");
        $stmtCount->execute([$idHotel]);
        $orden = (int)$stmtCount->fetchColumn() + 1;

        $stmt = $this->db->prepare("
            INSERT INTO fotos_hotel (id_hotel, url_foto, nombre_archivo, descripcion, es_portada, orden)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$idHotel, $nombre, $archivo['name'], $descripcion, $esPortada ? 1 : 0, $orden]);

        return ['success' => true, 'foto' => $nombre, 'message' => 'Foto subida exitosamente.'];
    }

    // -------------------------------------------------------
    //  SUBIR MÚLTIPLES fotos de una vez
    // -------------------------------------------------------
    public function subirMultiplesFotos(int $idHotel, array $archivos): array {
        $resultados    = [];
        $archivosArray = $this->reorganizarArchivos($archivos);

        foreach ($archivosArray as $index => $archivo) {
            $esPortada    = ($index === 0 && !$this->tienePortada($idHotel));
            $resultados[] = $this->subirFoto($idHotel, $archivo, '', $esPortada);
        }
        return $resultados;
    }

    // -------------------------------------------------------
    //  ELIMINAR foto
    // -------------------------------------------------------
    public function eliminarFoto(int $idFoto): array {
        $stmt = $this->db->prepare("SELECT * FROM fotos_hotel WHERE id_foto = ?");
        $stmt->execute([$idFoto]);
        $foto = $stmt->fetch();

        if (!$foto) return ['success' => false, 'message' => 'Foto no encontrada.'];

        $ruta = UPLOAD_PATH . 'hoteles/' . $foto['id_hotel'] . '/' . $foto['url_foto'];
        if (file_exists($ruta)) unlink($ruta);

        $this->db->prepare("DELETE FROM fotos_hotel WHERE id_foto = ?")->execute([$idFoto]);

        return ['success' => true, 'message' => 'Foto eliminada.'];
    }

    // -------------------------------------------------------
    //  MARCAR foto como portada
    // -------------------------------------------------------
    public function marcarComoPortada(int $idFoto, int $idHotel): bool {
        $this->db->prepare("UPDATE fotos_hotel SET es_portada = 0 WHERE id_hotel = ?")
                 ->execute([$idHotel]);

        $stmt = $this->db->prepare("SELECT url_foto FROM fotos_hotel WHERE id_foto = ?");
        $stmt->execute([$idFoto]);
        $foto = $stmt->fetch();

        $this->db->prepare("UPDATE fotos_hotel SET es_portada = 1 WHERE id_foto = ?")
                 ->execute([$idFoto]);
        $this->db->prepare("UPDATE hoteles SET foto_portada = ? WHERE id_hotel = ?")
                 ->execute([$foto['url_foto'], $idHotel]);

        return true;
    }

    // -------------------------------------------------------
    //  GESTIÓN DE SERVICIOS
    // -------------------------------------------------------
    public function actualizarServicios(int $idHotel, array $idsServicios): void {
        $this->db->prepare("DELETE FROM hotel_servicios WHERE id_hotel = ?")->execute([$idHotel]);
        $stmt = $this->db->prepare("INSERT INTO hotel_servicios (id_hotel, id_servicio) VALUES (?, ?)");
        foreach ($idsServicios as $idServicio) {
            $stmt->execute([$idHotel, (int)$idServicio]);
        }
    }

    public function getServicios(int $idHotel): array {
        $stmt = $this->db->prepare("
            SELECT s.* FROM servicios s
            INNER JOIN hotel_servicios hs ON hs.id_servicio = s.id_servicio
            WHERE hs.id_hotel = ?
            ORDER BY s.categoria, s.nombre
        ");
        $stmt->execute([$idHotel]);
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------
    //  HELPERS
    // -------------------------------------------------------
    public function getFotos(int $idHotel): array {
        $stmt = $this->db->prepare("SELECT * FROM fotos_hotel WHERE id_hotel = ? ORDER BY es_portada DESC, orden ASC");
        $stmt->execute([$idHotel]);
        return $stmt->fetchAll();
    }

    public function getTiposHabitacion(int $idHotel): array {
        $stmt = $this->db->prepare("SELECT * FROM tipo_habitacion WHERE id_hotel = ? AND activo = 1 ORDER BY precio_base");
        $stmt->execute([$idHotel]);
        return $stmt->fetchAll();
    }

    private function tienePortada(int $idHotel): bool {
        $stmt = $this->db->prepare("SELECT id_foto FROM fotos_hotel WHERE id_hotel = ? AND es_portada = 1");
        $stmt->execute([$idHotel]);
        return (bool)$stmt->fetch();
    }

    private function reorganizarArchivos(array $archivos): array {
        $resultado = [];
        $total     = count($archivos['name']);
        for ($i = 0; $i < $total; $i++) {
            $resultado[] = [
                'name'     => $archivos['name'][$i],
                'type'     => $archivos['type'][$i],
                'tmp_name' => $archivos['tmp_name'][$i],
                'error'    => $archivos['error'][$i],
                'size'     => $archivos['size'][$i],
            ];
        }
        return $resultado;
    }
}