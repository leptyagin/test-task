<?php

declare(strict_types=1);

use App\Database\Migration;

return new class () extends Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE articles (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                image VARCHAR(255) NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NULL,
                text MEDIUMTEXT NOT NULL,
                views INT UNSIGNED NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS articles');
    }
};
