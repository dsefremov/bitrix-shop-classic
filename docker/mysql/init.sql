-- Отдельная база для PHPUnit (наполняет docker/test-db.sh копией рабочей базы без данных магазина).
CREATE DATABASE IF NOT EXISTS `bitrix_shop_classic_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `bitrix_shop_classic_test`.* TO 'shop'@'%';
FLUSH PRIVILEGES;
