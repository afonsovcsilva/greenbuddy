-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: mysql:3306
-- Tempo de geração: 14-Jun-2026 às 13:55
-- Versão do servidor: 8.0.46
-- versão do PHP: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de dados: `greenbuddydb`
--
CREATE DATABASE IF NOT EXISTS `greenbuddydb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE `greenbuddydb`;

-- --------------------------------------------------------

--
-- Estrutura da tabela `dispositivos_validos`
--

CREATE TABLE `dispositivos_validos` (
  `mac_address` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `dispositivos_validos`
--

INSERT INTO `dispositivos_validos` (`mac_address`) VALUES
('11:22:33:44:55:66'),
('AA:BB:CC:DD:EE:FF'),
('C3:R4:EF:23:45:ED'),
('D4:E3:00:01:48:9D');

-- --------------------------------------------------------

--
-- Estrutura da tabela `utilizadores`
--

CREATE TABLE `utilizadores` (
  `id_utilizador` int NOT NULL,
  `username` varchar(250) NOT NULL,
  `senha` varchar(250) NOT NULL,
  `email` varchar(250) NOT NULL,
  `telemovel` int NOT NULL,
  `is_admin` tinyint(1) DEFAULT '0',
  `status` varchar(20) DEFAULT 'ativo',
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `utilizadores`
--

INSERT INTO `utilizadores` (`id_utilizador`, `username`, `senha`, `email`, `telemovel`, `is_admin`, `status`, `token_recuperacao`, `token_expira`) VALUES
(5, 'jlm', '$2y$10$BjBLnW6bGvkwOscqPoEMa.hnFQ5BxVGVESyo1LxKruex70Da62MY.', 'jlm@oficina.pt', 123456789, 0, 'ativo', NULL, NULL),
(7, 'Tiago Silva', '$2y$10$hTvKB.dPFnq4s7D7F4Tz2.GtouEAfgzcoEGRrvTvGVHm7nYxfXK8C', 'tiago.correiadasilva@gmail.com', 917197106, 0, 'ativo', NULL, NULL),
(10, 'afonso123', '$2y$10$RD6T.5hkQogT4PJmViYCwezUauqDRZtmyhQ0kmTXlyaKWyS.wP9aO', 'afonsovcsilva@gmail.com', 938490159, 0, 'ativo', NULL, NULL),
(11, 'afonsopimenta', '$2y$10$QjxQ/pk/hU4LzgeSdLOz/uHcc9Ph2GR42asWWep5yuNSLHsH4ZmKO', 'afonsopimenta08@gmail.com', 123456789, 0, 'ativo', NULL, NULL),
(14, 'admin', '$2y$10$0wL5xkO/7Wh9fNoqhBcWc.QZHHHnNmpS0uUYsq7JJ58sgGTOHE/Lm', 'admin@gmail.com', 938490159, 1, 'ativo', NULL, NULL),
(20, 'leo', '$2y$10$PPryq1Ud1bbL4F.8DgcJ5O8gVOOVNEFEZ4F/Rw5PYclBTKLp3F0Iu', 'a14479@oficina.pt', 928031645, 0, 'ativo', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura da tabela `vasos`
--

CREATE TABLE `vasos` (
  `id_vaso` int NOT NULL,
  `id_utilizador` int NOT NULL,
  `nome_vaso` varchar(50) NOT NULL,
  `mac_address` varchar(50) NOT NULL,
  `status_vaso` varchar(20) DEFAULT 'ativo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Extraindo dados da tabela `vasos`
--

INSERT INTO `vasos` (`id_vaso`, `id_utilizador`, `nome_vaso`, `mac_address`, `status_vaso`) VALUES
(8, 10, 'Vaso principal', 'D4:E3:00:01:48:9D', 'ativo'),
(9, 10, 'Vaso secundário', 'AA:BB:CC:DD:EE:FF', 'ativo'),
(10, 11, 'Meu Vaso Principal', '11:22:33:44:55:66', 'ativo');

-- --------------------------------------------------------

--
-- Estrutura da tabela `vaso_config`
--

CREATE TABLE `vaso_config` (
  `id` int NOT NULL,
  `seco_limite` int NOT NULL DEFAULT '30',
  `humido_limite` int NOT NULL DEFAULT '70',
  `autonomia_estimada` varchar(50) DEFAULT NULL,
  `data_reset` datetime DEFAULT CURRENT_TIMESTAMP,
  `email_enviado` tinyint(1) DEFAULT '0',
  `status_rega` int DEFAULT '1'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `vaso_config`
--

INSERT INTO `vaso_config` (`id`, `seco_limite`, `humido_limite`, `autonomia_estimada`, `data_reset`, `email_enviado`, `status_rega`) VALUES
(10, 20, 30, NULL, '2026-05-22 08:16:57', 0, 1),
(8, 10, 80, NULL, '2026-06-12 15:25:17', 0, 1),
(9, 30, 80, NULL, '2026-06-12 15:25:25', 0, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `vaso_humidade`
--

CREATE TABLE `vaso_humidade` (
  `id_humidade` int NOT NULL,
  `data` varchar(100) NOT NULL,
  `hora` varchar(50) NOT NULL,
  `percentagem` varchar(50) NOT NULL,
  `mac_address` varchar(20) DEFAULT NULL,
  `nivel_agua` int DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `dispositivos_validos`
--
ALTER TABLE `dispositivos_validos`
  ADD PRIMARY KEY (`mac_address`);

--
-- Índices para tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  ADD PRIMARY KEY (`id_utilizador`);

--
-- Índices para tabela `vasos`
--
ALTER TABLE `vasos`
  ADD PRIMARY KEY (`id_vaso`),
  ADD KEY `fk_vaso_utilizador` (`id_utilizador`);

--
-- Índices para tabela `vaso_config`
--
ALTER TABLE `vaso_config`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `vaso_humidade`
--
ALTER TABLE `vaso_humidade`
  ADD PRIMARY KEY (`id_humidade`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  MODIFY `id_utilizador` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de tabela `vasos`
--
ALTER TABLE `vasos`
  MODIFY `id_vaso` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `vaso_humidade`
--
ALTER TABLE `vaso_humidade`
  MODIFY `id_humidade` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5065;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `vasos`
--
ALTER TABLE `vasos`
  ADD CONSTRAINT `fk_vaso_utilizador` FOREIGN KEY (`id_utilizador`) REFERENCES `utilizadores` (`id_utilizador`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
