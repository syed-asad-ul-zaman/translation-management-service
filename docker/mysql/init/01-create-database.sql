CREATE DATABASE IF NOT EXISTS translation_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'translation_user'@'%' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON translation_management.* TO 'translation_user'@'%';
FLUSH PRIVILEGES;
