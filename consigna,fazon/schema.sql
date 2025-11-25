-- Database and tables for LADE,LINGO

-- 1) Create database
CREATE DATABASE IF NOT EXISTS consigna_fazon CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE consigna_fazon;

-- 2) Users table (for authentication)
CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3) Books table
CREATE TABLE IF NOT EXISTS books (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  author VARCHAR(255) NOT NULL,
  year INT UNSIGNED NOT NULL
) ENGINE=InnoDB;


