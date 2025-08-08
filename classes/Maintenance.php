<?php
class Maintenance {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll($filters = []) {
        $sql = "
            SELECT m.*, v.nome as veiculo_nome, v.placa
            FROM manutencoes m
            JOIN veiculos v ON m.veiculo_id = v.id
        ";
        
        $where = [];
        $params = [];
        
        if (!empty($filters['veiculo_id'])) {
            $where[] = "m.veiculo_id = ?";
            $params[] = $filters['veiculo_id'];
        }
        
        if (!empty($filters['tipo'])) {
            $where[] = "m.tipo LIKE ?";
            $params[] = '%' . $filters['tipo'] . '%';
        }
        
        if (!empty($filters['status'])) {
            $where[] = "m.status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['data_inicio'])) {
            $where[] = "m.data_manutencao >= ?";
            $params[] = $filters['data_inicio'];
        }
        
        if (!empty($filters['data_fim'])) {
            $where[] = "m.data_manutencao <= ?";
            $params[] = $filters['data_fim'];
        }
        
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY m.data_manutencao DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT m.*, v.nome as veiculo_nome, v.placa
            FROM manutencoes m
            JOIN veiculos v ON m.veiculo_id = v.id
            WHERE m.id = ?
        ");
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO manutencoes (veiculo_id, tipo, data_manutencao, km_manutencao, valor, descricao, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $data['veiculo_id'],
                $data['tipo'],
                $data['data_manutencao'],
                $data['km_manutencao'],
                $data['valor'] ?: 0,
                $data['descricao'] ?: null,
                $data['status']
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function update($id, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE manutencoes SET 
                    veiculo_id = ?, tipo = ?, data_manutencao = ?, km_manutencao = ?, 
                    valor = ?, descricao = ?, status = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $data['veiculo_id'],
                $data['tipo'],
                $data['data_manutencao'],
                $data['km_manutencao'],
                $data['valor'] ?: 0,
                $data['descricao'] ?: null,
                $data['status'],
                $id
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM manutencoes WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getOverdue() {
        $stmt = $this->db->prepare("
            SELECT m.*, v.nome as veiculo_nome, v.placa
            FROM manutencoes m
            JOIN veiculos v ON m.veiculo_id = v.id
            WHERE m.data_manutencao < CURDATE() AND m.status = 'agendada'
            ORDER BY m.data_manutencao ASC
        ");
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    public function getUpcoming($days = 7) {
        $stmt = $this->db->prepare("
            SELECT m.*, v.nome as veiculo_nome, v.placa
            FROM manutencoes m
            JOIN veiculos v ON m.veiculo_id = v.id
            WHERE m.data_manutencao BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY) 
            AND m.status = 'agendada'
            ORDER BY m.data_manutencao ASC
        ");
        $stmt->execute([$days]);
        
        return $stmt->fetchAll();
    }
}
?>