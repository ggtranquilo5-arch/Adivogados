-- SQL script for the Legal Management System
-- Optimized for MySQL / MariaDB (standard for InfinityFree)

CREATE TABLE IF NOT EXISTS `settings` (
  `key` VARCHAR(50) PRIMARY KEY,
  `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `cpf` VARCHAR(20) NOT NULL,
  `rg` VARCHAR(20) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `address_number` VARCHAR(20) NOT NULL,
  `contact` VARCHAR(50) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'lawyer',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `customer_cpf` VARCHAR(20) NOT NULL,
  `customer_contact` VARCHAR(50) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `lawyer_id` INT DEFAULT NULL,
  `cancellation_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`lawyer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `user_name` VARCHAR(150) NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default configurations if not exists
INSERT INTO `settings` (`key`, `value`) VALUES 
('company_name', 'Central de Advocacia Inteligente') 
ON DUPLICATE KEY UPDATE `key`=`key`;

-- Insert default admin user if not exists
-- The default password is '@Joaquim2006' (hashed using BCRYPT)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `cpf`, `rg`, `city`, `address_number`, `contact`, `role`, `status`) VALUES 
(1, 'Joaquim Moura', 'joaquim.moura@aluno.ifsertao-pe.edu.br', '$2y$10$ATs1d4Hh1l8SYdfTV/YPCOVeWAeLiQiXkadNq2cbfGAUIXje3Jovm', '000.000.000-00', '0000000000', 'São Paulo', '100', '(11) 99999-9999', 'admin', 'active')
ON DUPLICATE KEY UPDATE `id`=`id`;
