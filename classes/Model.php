<?php
// ============================================================
//  Clase Model — Clase base para todos los modelos OOP
// ============================================================

require_once __DIR__ . '/Database.php';

abstract class Model {
    protected PDO $db;
    protected string $table;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // Obtener todos los registros
    public function getAll(string $orderBy = 'id'): array {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY {$orderBy}");
        return $stmt->fetchAll();
    }

    // Obtener por ID
    public function getById(int $id): array|false {
        $pk = $this->getPrimaryKey();
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$pk} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Eliminar por ID
    public function delete(int $id): bool {
        $pk = $this->getPrimaryKey();
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$pk} = ?");
        return $stmt->execute([$id]);
    }

    // Contar registros
    public function count(string $where = ''): int {
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if ($where) $sql .= " WHERE {$where}";
        return (int) $this->db->query($sql)->fetchColumn();
    }

    // Clave primaria por convención (puede sobrescribirse)
    protected function getPrimaryKey(): string {
        return 'id_' . rtrim($this->table, 's');
    }
}
