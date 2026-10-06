CREATE DATABASE IF NOT EXISTS `internara`;
CREATE USER IF NOT EXISTS 'internara'@'%' IDENTIFIED BY 'Password321';
ALTER USER 'internara'@'%' IDENTIFIED BY 'Password321';
CREATE USER IF NOT EXISTS 'internara'@'localhost' IDENTIFIED BY 'Password321';
ALTER USER 'internara'@'localhost' IDENTIFIED BY 'Password321';
GRANT ALL PRIVILEGES ON `internara`.* TO 'internara'@'%';
GRANT ALL PRIVILEGES ON `internara`.* TO 'internara'@'localhost';
FLUSH PRIVILEGES;
