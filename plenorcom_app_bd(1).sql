-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 08/08/2025 às 13:59
-- Versão do servidor: 10.11.13-MariaDB-cll-lve
-- Versão do PHP: 8.3.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `plenorcom_app_bd`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `logo` varchar(500) DEFAULT NULL,
  `fonte` varchar(100) NOT NULL DEFAULT 'Inter',
  `cor_primaria` varchar(7) NOT NULL DEFAULT '#3b82f6',
  `cor_secundaria` varchar(7) NOT NULL DEFAULT '#64748b',
  `cor_destaque` varchar(7) NOT NULL DEFAULT '#f59e0b',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nome_sistema` varchar(255) NOT NULL DEFAULT 'Sistema de Controle de Veículos',
  `endereco_posto` text DEFAULT NULL,
  `telefone_posto` varchar(20) DEFAULT NULL,
  `horario_funcionamento` text DEFAULT NULL,
  `observacoes_gerais` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes_localizacao`
--

CREATE TABLE `configuracoes_localizacao` (
  `id` int(11) NOT NULL,
  `endereco_base` text NOT NULL COMMENT 'Endereço base da empresa/posto',
  `latitude_base` decimal(10,8) NOT NULL,
  `longitude_base` decimal(11,8) NOT NULL,
  `raio_tolerancia` int(11) DEFAULT 100 COMMENT 'Raio em metros para considerar que está no local',
  `intervalo_captura` int(11) DEFAULT 3600 COMMENT 'Intervalo em segundos para captura (1 hora = 3600)',
  `tempo_limite_base` int(11) DEFAULT 3600 COMMENT 'Tempo em segundos no local base para enviar alerta (1 hora = 3600)',
  `email_notificacao` varchar(255) DEFAULT NULL COMMENT 'Email para notificações administrativas',
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `deslocamentos`
--

CREATE TABLE `deslocamentos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `veiculo_id` int(11) NOT NULL,
  `destino` varchar(255) NOT NULL,
  `km_saida` int(11) NOT NULL,
  `km_retorno` int(11) DEFAULT NULL,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `status` enum('active','completed') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `localizacoes`
--

CREATE TABLE `localizacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `deslocamento_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `endereco` text DEFAULT NULL,
  `tipo` enum('inicio','tracking','fim') NOT NULL DEFAULT 'tracking',
  `data_captura` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `manutencoes`
--

CREATE TABLE `manutencoes` (
  `id` int(11) NOT NULL,
  `veiculo_id` int(11) NOT NULL,
  `tipo` varchar(255) NOT NULL,
  `data_manutencao` date NOT NULL,
  `km_manutencao` int(11) NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes_localizacao`
--

CREATE TABLE `notificacoes_localizacao` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `deslocamento_id` int(11) DEFAULT NULL,
  `tipo` enum('email_finalizacao','alerta_admin') NOT NULL,
  `email_destinatario` varchar(255) NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `mensagem` text NOT NULL,
  `enviado` tinyint(1) DEFAULT 0,
  `data_envio` timestamp NULL DEFAULT NULL,
  `erro` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `documento` varchar(50) DEFAULT NULL,
  `validade_cnh` date DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto` varchar(500) DEFAULT NULL,
  `perfil` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `force_password_change` tinyint(1) NOT NULL DEFAULT 0,
  `foto_cnh` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `veiculos`
--

CREATE TABLE `veiculos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `placa` varchar(10) NOT NULL,
  `troca_oleo` int(11) NOT NULL,
  `hodometro_atual` int(11) NOT NULL,
  `alinhamento` int(11) NOT NULL,
  `observacoes` text DEFAULT NULL,
  `foto` varchar(500) DEFAULT NULL,
  `disponivel` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `documento_vencimento` date DEFAULT NULL,
  `tipo_documento` varchar(50) DEFAULT 'CRLV'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `configuracoes_localizacao`
--
ALTER TABLE `configuracoes_localizacao`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `deslocamentos`
--
ALTER TABLE `deslocamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `veiculo_id` (`veiculo_id`),
  ADD KEY `idx_deslocamentos_usuario` (`usuario_id`),
  ADD KEY `idx_deslocamentos_veiculo` (`veiculo_id`),
  ADD KEY `idx_deslocamentos_status` (`status`);

--
-- Índices de tabela `localizacoes`
--
ALTER TABLE `localizacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_deslocamento_id` (`deslocamento_id`),
  ADD KEY `idx_data_captura` (`data_captura`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Índices de tabela `manutencoes`
--
ALTER TABLE `manutencoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `veiculo_id` (`veiculo_id`),
  ADD KEY `idx_manutencoes_veiculo` (`veiculo_id`);

--
-- Índices de tabela `notificacoes_localizacao`
--
ALTER TABLE `notificacoes_localizacao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_usuario_id` (`usuario_id`),
  ADD KEY `idx_deslocamento_id` (`deslocamento_id`),
  ADD KEY `idx_enviado` (`enviado`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_force_password` (`force_password_change`);

--
-- Índices de tabela `veiculos`
--
ALTER TABLE `veiculos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `placa` (`placa`),
  ADD KEY `idx_veiculos_disponivel` (`disponivel`),
  ADD KEY `idx_veiculos_ativo` (`ativo`),
  ADD KEY `idx_veiculos_documento_vencimento` (`documento_vencimento`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `configuracoes_localizacao`
--
ALTER TABLE `configuracoes_localizacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `deslocamentos`
--
ALTER TABLE `deslocamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `localizacoes`
--
ALTER TABLE `localizacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `manutencoes`
--
ALTER TABLE `manutencoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `notificacoes_localizacao`
--
ALTER TABLE `notificacoes_localizacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `veiculos`
--
ALTER TABLE `veiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `deslocamentos`
--
ALTER TABLE `deslocamentos`
  ADD CONSTRAINT `deslocamentos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `deslocamentos_ibfk_2` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON UPDATE CASCADE;

--
-- Restrições para tabelas `localizacoes`
--
ALTER TABLE `localizacoes`
  ADD CONSTRAINT `localizacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `localizacoes_ibfk_2` FOREIGN KEY (`deslocamento_id`) REFERENCES `deslocamentos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `manutencoes`
--
ALTER TABLE `manutencoes`
  ADD CONSTRAINT `manutencoes_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `notificacoes_localizacao`
--
ALTER TABLE `notificacoes_localizacao`
  ADD CONSTRAINT `notificacoes_localizacao_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `notificacoes_localizacao_ibfk_2` FOREIGN KEY (`deslocamento_id`) REFERENCES `deslocamentos` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
