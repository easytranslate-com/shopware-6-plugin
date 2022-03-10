<?php declare(strict_types=1);

namespace Wexo\EasyTranslate\Core\Content\EasyTranslateTask;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class EasyTranslateTaskCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EasyTranslateTaskEntity::class;
    }
}
