<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1639671493EasyTranslateTask extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1639671493;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `easytranslate_task` (
                `id`                       BINARY(16)   NOT NULL,
                `easytranslate_project_id` BINARY(16)   NOT NULL,
                `target_language_id`       BINARY(16)   NOT NULL,
                `easytranslate_id`         VARCHAR(255),
                `status`                   VARCHAR(255) NOT NULL,
                `created_at`               DATETIME(3)  NOT NULL,
                `updated_at`               DATETIME(3)  NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.easytranslate_task.easytranslate_project_id` FOREIGN KEY (`easytranslate_project_id`)
                    REFERENCES `easytranslate_project` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.easytranslate_task.target_language_id` FOREIGN KEY (`target_language_id`)
                    REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;

        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
