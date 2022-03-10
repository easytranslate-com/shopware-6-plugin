<?php declare(strict_types=1);

namespace Wexo\EasyTranslate;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Wexo\EasyTranslate\Migration\Migration1638442394EasyTranslateProject;
use Wexo\EasyTranslate\Migration\Migration1639671493EasyTranslateTask;

/**
 * Class WexoEasyTranslate
 * @package Wexo\EasyTranslate
 */
class WexoEasyTranslate extends Plugin
{
    const LOG_CHANNEL = 'easytranslate';

    const EVENT_TASK_UPDATE = 'task.updated';
    const EVENT_PROJECT_STATUS_APPROVAL = 'project.status.approval_needed';
    const EVENT_PROJECT_STATUS_ACCEPTED = 'project.status.price_accepted';
    const EVENT_PROJECT_STATUS_DECLINED = 'project.status.price_declined';

    const PROJECT_STATUS_INIT = 'INIT';
    const PROJECT_STATUS_CREATED = 'CREATED';
    const PROJECT_STATUS_APPROVAL_NEEDED = 'APPROVAL_NEEDED';
    const PROJECT_STATUS_APPROVED = 'APPROVED';
    const PROJECT_STATUS_DECLINED = 'DECLINED';
    const PROJECT_STATUS_COMPLETED = 'COMPLETED';

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        $connection->executeStatement('DROP TABLE IF EXISTS `easytranslate_project_product`');
        $connection->executeStatement('DROP TABLE IF EXISTS `easytranslate_project_category`');
        $connection->executeStatement('DROP TABLE IF EXISTS `easytranslate_project_target_language`');
        $connection->executeStatement('DROP TABLE IF EXISTS `easytranslate_project`');

        $connection->executeStatement('DROP TABLE IF EXISTS `easytranslate_task`');

        // TODO: Figure out if already completed translations should be removed from categories and products

        // TODO: Figure out if language, category and product extensions should be handled

        // TODO: Figure out if snippets should be removed
    }
}
