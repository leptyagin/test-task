<?php

declare(strict_types=1);

use App\Database\Migration;

return new class () extends Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE article_category (
                article_id INT UNSIGNED NOT NULL,
                category_id INT UNSIGNED NOT NULL,
                PRIMARY KEY (article_id, category_id),
                KEY idx_article_category_category (category_id),
                CONSTRAINT fk_article_category_article
                    FOREIGN KEY (article_id) REFERENCES articles (id) ON DELETE CASCADE,
                CONSTRAINT fk_article_category_category
                    FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS article_category');
    }
};
