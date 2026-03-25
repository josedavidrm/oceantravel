<?php
// ============================================================
//  Clase Usuario — Gestión de usuarios del sistema
// ============================================================

require_once __DIR__ . '/Model.php';

class Usuario extends Model {
    protected string $table = 'usuarios';

    // Propiedades del objeto
    public int    $id_usuario;
    public string $nombre;
    public string $apellido;
    public string $email;
    public string $contrasena;
    public string $telefono;
    public string $rol;
    public string $estado;
    public ?string $foto_perfil;
    public string $fecha_registro;

    // -------------------------------------------------------
    //  REGISTRO de nuevo usuario
    // -------------------------------------------------------
    public function registrar(array $datos): array {
        // Validaciones
        if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email inválido.'];
        }
        if (strlen($datos['contrasena']) < 8) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres.'];
        }
        if ($this->emailExiste($datos['email'])) {
            return ['success' => false, 'message' => 'Este email ya está registrado.'];
        }

        $hash = password_hash($datos['contrasena'], PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $this->db->prepare("
            INSERT INTO usuarios (nombre, apellido, email, contrasena, telefono, rol)
            VALUES (:nombre, :apellido, :email, :contrasena, :telefono, :rol)
        ");
        $stmt->execute([
            ':nombre'     => htmlspecialchars(trim($datos['nombre'])),
            ':apellido'   => htmlspecialchars(trim($datos['apellido'])),
            ':email'      => strtolower(trim($datos['email'])),
            ':contrasena' => $hash,
            ':telefono'   => $datos['telefono'] ?? null,
            ':rol'        => $datos['rol'] ?? 'cliente',
        ]);

        return ['success' => true, 'id' => $this->db->lastInsertId(), 'message' => 'Usuario registrado exitosamente.'];
    }

    // -------------------------------------------------------
    //  LOGIN
    // -------------------------------------------------------
    public function login(string $email, string $contrasena): array {
        $stmt = $this->db->prepare("
            SELECT * FROM usuarios WHERE email = ? AND estado = 'activo' LIMIT 1
        ");
        $stmt->execute([strtolower(trim($email))]);
        $usuario = $stmt->fetch();

        if (!$usuario || !password_verify($contrasena, $usuario['contrasena'])) {
            return ['success' => false, 'message' => 'Credenciales incorrectas.'];
        }

        // Actualizar último acceso
        $this->db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?")
                 ->execute([$usuario['id_usuario']]);

        // Crear sesión
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+8 hours'));

        $this->db->prepare("
            INSERT INTO sesiones (id_usuario, token, ip_acceso, fecha_expiracion)
            VALUES (?, ?, ?, ?)
        ")->execute([$usuario['id_usuario'], $token, $_SERVER['REMOTE_ADDR'] ?? null, $expira]);

        // Guardar en sesión PHP
        session_start();
        $_SESSION['id_usuario']  = $usuario['id_usuario'];
        $_SESSION['nombre']      = $usuario['nombre'];
        $_SESSION['apellido']    = $usuario['apellido'];
        $_SESSION['email']       = $usuario['email'];
        $_SESSION['rol']         = $usuario['rol'];
        $_SESSION['token']       = $token;
        $_SESSION['foto_perfil'] = $usuario['foto_perfil'];

        return [
            'success'  => true,
            'message'  => 'Bienvenido, ' . $usuario['nombre'],
            'rol'      => $usuario['rol'],
            'usuario'  => $usuario,
        ];
    }

    // -------------------------------------------------------
    //  LOGOUT
    // -------------------------------------------------------
    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (isset($_SESSION['token'])) {
            $this->db->prepare("UPDATE sesiones SET activa = 0 WHERE token = ?")
                     ->execute([$_SESSION['token']]);
        }
        session_destroy();
        header('Location: ' . SITE_URL . '/pages/login.php');
        exit;
    }

    // -------------------------------------------------------
    //  VERIFICAR sesión activa
    // -------------------------------------------------------
    public static function verificarSesion(string $rolRequerido = ''): void {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['id_usuario'])) {
            header('Location: ' . SITE_URL . '/pages/login.php');
            exit;
        }
        if ($rolRequerido && $_SESSION['rol'] !== $rolRequerido) {
            header('Location: ' . SITE_URL . '/pages/acceso-denegado.php');
            exit;
        }
    }

    // -------------------------------------------------------
    //  ACTUALIZAR perfil
    // -------------------------------------------------------
    public function actualizar(int $id, array $datos): array {
        $stmt = $this->db->prepare("
            UPDATE usuarios SET nombre = :nombre, apellido = :apellido,
            telefono = :telefono WHERE id_usuario = :id
        ");
        $stmt->execute([
            ':nombre'   => htmlspecialchars(trim($datos['nombre'])),
            ':apellido' => htmlspecialchars(trim($datos['apellido'])),
            ':telefono' => $datos['telefono'] ?? null,
            ':id'       => $id,
        ]);
        return ['success' => true, 'message' => 'Perfil actualizado.'];
    }

    // -------------------------------------------------------
    //  CAMBIAR contraseña
    // -------------------------------------------------------
    public function cambiarContrasena(int $id, string $actual, string $nueva): array {
        $stmt = $this->db->prepare("SELECT contrasena FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if (!password_verify($actual, $row['contrasena'])) {
            return ['success' => false, 'message' => 'La contraseña actual es incorrecta.'];
        }
        if (strlen($nueva) < 8) {
            return ['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres.'];
        }

        $hash = password_hash($nueva, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->prepare("UPDATE usuarios SET contrasena = ? WHERE id_usuario = ?")
                 ->execute([$hash, $id]);

        return ['success' => true, 'message' => 'Contraseña actualizada exitosamente.'];
    }

    // -------------------------------------------------------
    //  LISTAR usuarios (para admin)
    // -------------------------------------------------------
    public function listarTodos(string $rol = '', string $estado = 'activo'): array {
        $sql = "SELECT id_usuario, nombre, apellido, email, telefono, rol, estado, fecha_registro, ultimo_acceso
                FROM usuarios WHERE 1=1";
        $params = [];
        if ($rol) { $sql .= " AND rol = ?"; $params[] = $rol; }
        if ($estado) { $sql .= " AND estado = ?"; $params[] = $estado; }
        $sql .= " ORDER BY fecha_registro DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // -------------------------------------------------------
    //  CAMBIAR estado (activar/desactivar)
    // -------------------------------------------------------
    public function cambiarEstado(int $id, string $estado): bool {
        $stmt = $this->db->prepare("UPDATE usuarios SET estado = ? WHERE id_usuario = ?");
        return $stmt->execute([$estado, $id]);
    }

    // -------------------------------------------------------
    //  SUBIR foto de perfil
    // -------------------------------------------------------
    public function subirFotoPerfil(int $id, array $archivo): array {
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Error al subir el archivo.'];
        }
        if (!in_array($archivo['type'], ALLOWED_IMAGE_TYPES)) {
            return ['success' => false, 'message' => 'Formato no permitido. Use JPG, PNG o WebP.'];
        }
        if ($archivo['size'] > MAX_UPLOAD_SIZE) {
            return ['success' => false, 'message' => 'La imagen no debe superar 5MB.'];
        }

        $ext      = pathinfo($archivo['name'], PATHINFO_EXTENSION);
        $nombre   = 'perfil_' . $id . '_' . time() . '.' . $ext;
        $destino  = UPLOAD_PATH . 'perfiles/' . $nombre;

        if (!is_dir(UPLOAD_PATH . 'perfiles/')) mkdir(UPLOAD_PATH . 'perfiles/', 0755, true);

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            return ['success' => false, 'message' => 'No se pudo guardar la imagen.'];
        }

        $this->db->prepare("UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?")
                 ->execute([$nombre, $id]);

        return ['success' => true, 'foto' => $nombre, 'message' => 'Foto actualizada.'];
    }

    // -------------------------------------------------------
    //  Helpers privados
    // -------------------------------------------------------
    private function emailExiste(string $email): bool {
        $stmt = $this->db->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([strtolower(trim($email))]);
        return (bool) $stmt->fetch();
    }

    public function getNombreCompleto(): string {
        return $this->nombre . ' ' . $this->apellido;
    }
}
