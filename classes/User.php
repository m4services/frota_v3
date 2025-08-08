<?php
class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll() {
        $stmt = $this->db->prepare("SELECT * FROM usuarios ORDER BY nome");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getDrivers() {
        $stmt = $this->db->prepare("SELECT id, nome FROM usuarios ORDER BY nome");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function create($data, $photo = null) {
        try {
            // Verificar se email já existe
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$data['email']]);
            
            if ($stmt->fetchColumn() > 0) {
                return false; // Email já existe
            }
            
            $photo_name = null;
            
            if ($photo && $photo['error'] === UPLOAD_ERR_OK) {
                $photo_name = $this->uploadPhoto($photo, 'usuarios');
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO usuarios (nome, documento, validade_cnh, email, senha, foto, perfil) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            return $stmt->execute([
                $data['nome'],
                $data['documento'],
                $data['validade_cnh'],
                $data['email'],
                password_hash($data['senha'], PASSWORD_DEFAULT),
                $photo_name,
                $data['perfil']
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function update($id, $data, $photo = null) {
        try {
            $user = $this->getById($id);
            if (!$user) return false;
            
            // Verificar se email já existe (exceto para o próprio usuário)
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$data['email'], $id]);
            
            if ($stmt->fetchColumn() > 0) {
                return false; // Email já existe
            }
            
            $photo_name = $user['foto'];
            
            if ($photo && $photo['error'] === UPLOAD_ERR_OK) {
                // Remover foto antiga
                if ($photo_name) {
                    $old_photo = UPLOADS_PATH . '/usuarios/' . $photo_name;
                    if (file_exists($old_photo)) {
                        unlink($old_photo);
                    }
                }
                $photo_name = $this->uploadPhoto($photo, 'usuarios');
            }
            
            $sql = "UPDATE usuarios SET nome = ?, documento = ?, validade_cnh = ?, email = ?, foto = ?, perfil = ?";
            $params = [$data['nome'], $data['documento'], $data['validade_cnh'], $data['email'], $photo_name, $data['perfil']];
            
            // Se senha foi informada, atualizar também
            if (!empty($data['senha'])) {
                $sql .= ", senha = ?";
                $params[] = password_hash($data['senha'], PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function updateProfile($id, $data) {
        try {
            $sql = "UPDATE usuarios SET nome = ?, documento = ?, validade_cnh = ?";
            $params = [$data['nome'], $data['documento'], $data['validade_cnh']];
            
            // Se senha foi informada, atualizar também
            if (!empty($data['senha'])) {
                $sql .= ", senha = ?";
                $params[] = password_hash($data['senha'], PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function delete($id) {
        try {
            $user = $this->getById($id);
            if (!$user) return false;
            
            // Não permitir excluir se há deslocamentos ativos
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM deslocamentos WHERE usuario_id = ? AND status = 'ativo'");
            $stmt->execute([$id]);
            
            if ($stmt->fetchColumn() > 0) {
                return false; // Usuário tem deslocamento ativo
            }
            
            // Remover foto
            if ($user['foto']) {
                $photo_path = UPLOADS_PATH . '/usuarios/' . $user['foto'];
                if (file_exists($photo_path)) {
                    unlink($photo_path);
                }
            }
            
            $stmt = $this->db->prepare("DELETE FROM usuarios WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function uploadPhoto($photo, $folder) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($photo['type'], $allowed_types)) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        if ($photo['size'] > $max_size) {
            throw new Exception('Arquivo muito grande');
        }
        
        $extension = pathinfo($photo['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $upload_path = UPLOADS_PATH . '/' . $folder . '/' . $filename;
        
        if (move_uploaded_file($photo['tmp_name'], $upload_path)) {
            return $filename;
        }
        
        throw new Exception('Erro ao fazer upload do arquivo');
    }
}
?>