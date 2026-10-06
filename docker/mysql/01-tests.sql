CREATE DATABASE IF NOT EXISTS runalyze_unittest   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS runalyze_test_empty CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'runalyze_test'@'%' IDENTIFIED BY 'password';

GRANT ALL PRIVILEGES ON runalyze_unittest.*   TO 'runalyze_test'@'%';
GRANT ALL PRIVILEGES ON runalyze_test_empty.* TO 'runalyze_test'@'%';

FLUSH PRIVILEGES;
