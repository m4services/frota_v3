<?php
class Config {
    private $db;
    private $default_config = [
        'id' => 1,
        'logo' => null,
        'fonte' => 'Inter',
        'cor_primaria' => '#007bff',
        'cor_secundaria' => '#6c757d',
        'cor_destaque' => '#28a745',
        'nome_empresa' => 'Sistema de Veículos'
    ];
    
    public function __construct() {
        try {
            $this->db = Database::getInstance()->getConnection();
        } catch (Exception $e) {
            error_log('Erro ao conectar Config com banco: ' . $e->getMessage());
            $this->db = null;
        }
    }
    
    public function get() {
        if (!$this->db) {
            return $this->default_config;
        }
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM configuracoes LIMIT 1");
            $stmt->execute();
            $config = $stmt->fetch();
            
            if (!$config) {
                // Tentar criar configuração padrão
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO configuracoes (logo, fonte, cor_primaria, cor_secundaria, cor_destaque, nome_empresa) 
                        VALUES (NULL, 'Inter', '#007bff', '#6c757d', '#28a745', 'Sistema de Veículos')
                    ");
                    $stmt->execute();
                    return $this->get();
                } catch (Exception $e) {
                    error_log('Erro ao criar configuração padrão: ' . $e->getMessage());
                    return $this->default_config;
                }
            }
            
            return $config;
        } catch (Exception $e) {
            error_log('Erro ao buscar configurações: ' . $e->getMessage());
            return $this->default_config;
        }
    }
    
    public function update($data, $logo = null) {
        if (!$this->db) {
            return false;
        }
        
        try {
            $config = $this->get();
            $logo_name = $config['logo'];
            
            if ($logo && $logo['error'] === UPLOAD_ERR_OK) {
                if ($logo_name) {
                    $old_logo = UPLOADS_PATH . '/logos/' . $logo_name;
                    if (file_exists($old_logo)) {
                        unlink($old_logo);
                    }
                }
                $logo_name = $this->uploadLogo($logo);
            }
            
            $stmt = $this->db->prepare("
                UPDATE configuracoes SET 
                    logo = ?, fonte = ?, cor_primaria = ?, cor_secundaria = ?, 
                    cor_destaque = ?, nome_empresa = ?
                WHERE id = ?
            ");
            
            return $stmt->execute([
                $logo_name,
                $data['fonte'],
                $data['cor_primaria'],
                $data['cor_secundaria'],
                $data['cor_destaque'],
                $data['nome_empresa'],
                $config['id']
            ]);
        } catch (Exception $e) {
            error_log('Erro ao atualizar configurações: ' . $e->getMessage());
            return false;
        }
    }
    
    private function uploadLogo($logo) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($logo['type'], $allowed_types)) {
            throw new Exception('Tipo de arquivo não permitido');
        }
        
        if ($logo['size'] > $max_size) {
            throw new Exception('Arquivo muito grande');
        }
        
        $extension = pathinfo($logo['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . time() . '.' . $extension;
        $upload_path = UPLOADS_PATH . '/logos/' . $filename;
        
        if (move_uploaded_file($logo['tmp_name'], $upload_path)) {
            return $filename;
        }
        
        throw new Exception('Erro ao fazer upload do logo');
    }
}
?>