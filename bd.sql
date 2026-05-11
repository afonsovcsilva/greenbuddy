-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql112.infinityfree.com
-- Tempo de geração: 28-Abr-2026 às 07:16
-- Versão do servidor: 11.4.10-MariaDB
-- versão do PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Banco de dados: `if0_41734926_greenbuddy`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `vaso`
--

CREATE TABLE `vaso` (
  `id` int(11) NOT NULL,
    `descricao` varchar(250) NOT NULL,
      `tamanho` varchar(200) NOT NULL,
        `localizacao` varchar(200) NOT NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

        -- --------------------------------------------------------

        --
        -- Estrutura da tabela `vaso_humidade`
        --

        CREATE TABLE `vaso_humidade` (
          `id_humidade` int(11) NOT NULL,
            `data` varchar(100) NOT NULL,
              `hora` varchar(50) NOT NULL,
                `percentagem` varchar(50) NOT NULL
                ) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

                --
                -- Extraindo dados da tabela `vaso_humidade`
                --

                INSERT INTO `vaso_humidade` (`id_humidade`, `data`, `hora`, `percentagem`) VALUES
                (13, '2026-04-28', '06:38:42', '3'),
                (12, '2026-04-28', '06:35:38', '3'),
                (11, '2026-04-28', '06:25:50', '15'),
                (10, '2026-04-28', '06:25:44', '12'),
                (9, '2026-04-28', '06:24:34', '12'),
                (8, '2026-04-28', '06:16:39', '10'),
                (14, '2026-04-28', '06:38:43', '3');

                --
                -- Índices para tabelas despejadas
                --

                --
                -- Índices para tabela `vaso`
                --
                ALTER TABLE `vaso`
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
                    -- AUTO_INCREMENT de tabela `vaso`
                    --
                    ALTER TABLE `vaso`
                      MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

                      --
                      -- AUTO_INCREMENT de tabela `vaso_humidade`
                      --
                      ALTER TABLE `vaso_humidade`
                        MODIFY `id_humidade` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;
                        COMMIT;
                        