CREATE DATABASE IF NOT EXISTS runalyze          CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS runalyze_test     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS runalyze_unittest CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'runalyze'@'%' IDENTIFIED BY 'runalyze';
DROP USER IF EXISTS 'runalyze_test'@'%';
CREATE USER 'runalyze_test'@'%' IDENTIFIED BY 'password';

GRANT ALL PRIVILEGES ON runalyze.*          TO 'runalyze'@'%';
GRANT ALL PRIVILEGES ON runalyze_test.*     TO 'runalyze'@'%';
GRANT ALL PRIVILEGES ON runalyze_unittest.* TO 'runalyze'@'%';

GRANT ALL PRIVILEGES ON runalyze_test.*     TO 'runalyze_test'@'%';
GRANT ALL PRIVILEGES ON runalyze_unittest.* TO 'runalyze_test'@'%';

FLUSH PRIVILEGES;
