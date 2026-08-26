<?php

declare(strict_types=1);

use App\Database\Migration;

return new class () extends Migration {
    public function up(PDO $pdo): void
    {
        $pdo->exec(
            'ALTER TABLE articles
                ADD COLUMN published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER views,
                ADD INDEX idx_articles_published_at (published_at)',
        );
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec(
            'ALTER TABLE articles
                DROP INDEX idx_articles_published_at,
                DROP COLUMN published_at',
        );
    }
};
