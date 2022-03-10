<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1638442394EasyTranslateProject extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1638442394;
    }

    public function update(Connection $connection): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `easytranslate_project` (
                `id`                 BINARY(16)   NOT NULL,
                `name`               VARCHAR(255) NOT NULL,
                `workflow`           VARCHAR(255) NOT NULL,
                `easytranslate_id`   VARCHAR(255),
                `translation_price`  DOUBLE,
                `source_language_id` BINARY(16)   NOT NULL,
                `status`             VARCHAR(255) NOT NULL,
                `created_at`         DATETIME(3)  NOT NULL,
                `updated_at`         DATETIME(3)  NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.easytranslate_project.source_language_id` FOREIGN KEY (`source_language_id`)
                    REFERENCES `language` (`id`) ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;

        $connection->executeStatement($sql);

        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `easytranslate_project_target_language` (
                `easytranslate_project_id` BINARY(16)  NOT NULL,
                `language_id`              BINARY(16)  NOT NULL,
                `created_at`               DATETIME(3) NOT NULL,
                PRIMARY KEY (`easytranslate_project_id`,`language_id`),
                KEY `fk.et_project_target_languages.easytranslate_project_id` (`easytranslate_project_id`),
                KEY `fk.et_project_target_languages.language_id` (`language_id`),
                CONSTRAINT `fk.et_project_target_languages.easytranslate_project_id` FOREIGN KEY (`easytranslate_project_id`)
                    REFERENCES `easytranslate_project` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.et_project_target_languages.language_id` FOREIGN KEY (`language_id`)
                    REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;

        $connection->executeStatement($sql);

        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `easytranslate_project_category` (
                `easytranslate_project_id` BINARY(16)  NOT NULL,
                `category_id`              BINARY(16)  NOT NULL,
                `category_version_id`      BINARY(16)  NOT NULL,
                `created_at`               DATETIME(3) NOT NULL,
                PRIMARY KEY (`easytranslate_project_id`,`category_id`, `category_version_id`),
                KEY `fk.et_project_categories.easytranslate_project_id` (`easytranslate_project_id`),
                KEY `fk.et_project_categories.category_id` (`category_id`),
                CONSTRAINT `fk.et_project_categories.easytranslate_project_id` FOREIGN KEY (`easytranslate_project_id`)
                    REFERENCES `easytranslate_project` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.et_project_categories.category_version_id__category_id` FOREIGN KEY (`category_id`, `category_version_id`)
                    REFERENCES `category` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;

        $connection->executeStatement($sql);

        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `easytranslate_project_product` (
                `easytranslate_project_id` BINARY(16)  NOT NULL,
                `product_id`               BINARY(16)  NOT NULL,
                `product_version_id`       BINARY(16)  NOT NULL,
                `created_at`               DATETIME(3) NOT NULL,
                PRIMARY KEY (`easytranslate_project_id`,`product_id`, `product_version_id`),
                KEY `fk.et_project_products.easytranslate_project_id` (`easytranslate_project_id`),
                KEY `fk.et_project_products.product_id` (`product_id`),
                CONSTRAINT `fk.et_project_products.easytranslate_project_id` FOREIGN KEY (`easytranslate_project_id`)
                    REFERENCES `easytranslate_project` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.et_project_products.product_version_id__product_id` FOREIGN KEY (`product_id`, `product_version_id`)
                    REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;

        $connection->executeStatement($sql);
        // phpcs:enable Generic.Files.LineLength.TooLong
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
