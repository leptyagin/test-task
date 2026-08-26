<?php

declare(strict_types=1);

use App\Core\Database;
use App\Database\Migrator;

require __DIR__ . '/../vendor/autoload.php';

$command = $argv[1] ?? 'help';
$migrator = new Migrator(Database::connection(), __DIR__ . '/../database/migrations');

switch ($command) {
    case 'migrate':
        $ran = $migrator->migrate();
        echo $ran === []
            ? "There are no new migrations\n"
            : "Applied:\n  " . implode("\n  ", $ran) . "\n";
        break;

    case 'migrate:rollback':
        $rolled = $migrator->rollback();
        echo $rolled === []
            ? "There is nothing to rollback\n"
            : "Rolled back:\n  " . implode("\n  ", $rolled) . "\n";
        break;

    case 'migrate:status':
        foreach ($migrator->status() as $name => $applied) {
            echo ($applied ? '  [x] ' : '  [ ] ') . $name . "\n";
        }
        break;

    case 'db:seed':
        $seeder = require __DIR__ . '/../database/seeders/DatabaseSeeder.php';
        $seeder(Database::connection());
        echo "Demo data loaded\n";
        break;

    default:
        echo <<<TXT
            Usage: php bin/console <command>

              migrate           apply new migrations
              migrate:rollback  rollback the last batch of migrations
              migrate:status    show the status of migrations
              db:seed           load demo data

            TXT;
}
