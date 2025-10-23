-- Create database
CREATE DATABASE IF NOT EXISTS petition_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE petition_db;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Petitions table
CREATE TABLE IF NOT EXISTS petitions (
    IDP INT AUTO_INCREMENT PRIMARY KEY,
    TitleP VARCHAR(500) NOT NULL,
    DescriptionP TEXT NOT NULL,
    DateAddedP DATETIME DEFAULT CURRENT_TIMESTAMP,
    EndDateP DATE NOT NULL,
    HolderNameP VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL,
    ImageUrl VARCHAR(500) NULL,
    userId INT NOT NULL,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (userId),
    INDEX idx_date (DateAddedP)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Signatures table
CREATE TABLE IF NOT EXISTS signatures (
    IDS INT AUTO_INCREMENT PRIMARY KEY,
    IDP INT NOT NULL,
    LastNameS VARCHAR(255) NOT NULL,
    FirstNameS VARCHAR(255) NOT NULL,
    CountryS VARCHAR(255) NOT NULL,
    DateS DATE NOT NULL,
    TimeS TIME NOT NULL,
    EmailS VARCHAR(255) NOT NULL,
    FOREIGN KEY (IDP) REFERENCES petitions(IDP) ON DELETE CASCADE,
    INDEX idx_petition (IDP),
    INDEX idx_date (DateS, TimeS)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User profiles table
CREATE TABLE IF NOT EXISTS profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    bio TEXT,
    location VARCHAR(255),
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (userId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User petitions tracking (which petitions a user created)
CREATE TABLE IF NOT EXISTS user_petitions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    petitionId INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (petitionId) REFERENCES petitions(IDP) ON DELETE CASCADE,
    UNIQUE KEY unique_user_petition (userId, petitionId),
    INDEX idx_user (userId),
    INDEX idx_petition (petitionId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User signatures tracking (which petitions a user signed)
CREATE TABLE IF NOT EXISTS user_signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userId INT NOT NULL,
    petitionId INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (petitionId) REFERENCES petitions(IDP) ON DELETE CASCADE,
    UNIQUE KEY unique_user_signature (userId, petitionId),
    INDEX idx_user (userId),
    INDEX idx_petition (petitionId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
